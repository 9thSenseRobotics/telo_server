<?php

require_once('officebot-include.php');
error_reporting(0); 
include ('robotMessages.php');

//$phonereg = '758fa234cb7edb05';
/**
 * Gets an authentication token for a Google service (defaults to
 * Picasa). Puts the token in a session variable and re-uses it as
 * needed, instead of fetching a new token for every call.
 *
 * @static
 * @access public
 * @param string $username Google email account
 * @param string $password Password for Google email account
 * @param string $source name of the calling application (defaults to your_google_app)
 * @param string $service name of the Google service to call (defaults to cloud to device messaging for Android)
 * @return boolean|string An authentication token, or false on failure
 */

 
function googleAuthenticate($username, $password, $source = 'org.abarry.telo', $service = 'ac2dm') {
    //$session_token = $source . '_' . $service . '_auth_token';
    $session_token = "auth_token";

    if ($_SESSION[$session_token]) {
        return $_SESSION[$session_token];
    }

    // get an authorization token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
    $post_fields = "accountType=" . urlencode('GOOGLE')
        . "&Email=" . urlencode($username)
        . "&Passwd=" . urlencode($password)
        . "&source=" . urlencode($source)
        . "&service=" . urlencode($service);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    
    curl_setopt($ch, CURLINFO_HEADER_OUT, true); // for debugging the request
    var_dump(curl_getinfo($ch,CURLINFO_HEADER_OUT)); //for debugging the request

    $response = curl_exec($ch);
    curl_close($ch);
    

    if (strpos($response, '200 OK') === false) {
        return false;
    }

    // find the auth code
    preg_match("/(Auth=)([\w|-]+)/", $response, $matches);

    if (!$matches[2]) {
        return false;
    }

    $_SESSION[$session_token] = $matches[2];
    return $matches[2];
}

/**
 * Sends a push notification to an Android device using Google C2DM when given a payload (under 1024 bytes),
 * the server authorization code, and the phone registration id.
 *
 * @param datain less than 1024 bytes of input to be sent to the device (string)
 * @param serverAuth server authorization obtained from googleAuthenticate
 * @param phoneRegistrationId registration of the target phone.  This must be obtained from the phone, and we get it out of a database on a previous page.
 */
function sendAndroidPush($datain, $serverAuth, $phoneRegistrationId)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://android.apis.google.com/c2dm/send");
     $post_fields = "registration_id=" . urlencode($phoneRegistrationId)
        . "&data.payload=" . urlencode($datain)
        . "&collapse_key=0";
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: GoogleLogin auth=' . $serverAuth));
    
    curl_setopt($ch, CURLINFO_HEADER_OUT, true); // for debugging the request
 //   var_dump(curl_getinfo($ch,CURLINFO_HEADER_OUT)); //for debugging the request


    $response = curl_exec($ch);
//    echo $response;
    curl_close($ch);
	return ($response);
}

function is_valid_create_command($cmd)
// make sure it's a command we understand
{ 
	return ($cmd);
}

function is_valid_robot_command($cmd)
// make sure it's a command we understand
{ 
	switch ($cmd)
	{
		case 'u':
		case 'd':
		case 'l':
		case 'r':
			return ($cmd);
		default: 
			return (false);
	}
}


function robot_command($cmd)
{
	global $conn, $createActionTable, $robotname, $sendStr, $phonereg;
	$ts = microtime_long();
	$latency = microtime(true);
	$commandSocket = socket_create (AF_INET, SOCK_STREAM, 0);
	socket_set_option($commandSocket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>2, "usec"=>500));
	socket_bind($commandSocket, '127.0.0.1');
	socket_connect($commandSocket, '127.0.0.1', '49441');
	socket_set_block($commandSocket);
	
	if (isset($_REQUEST['speed']))
	{
		$s = $_REQUEST['speed'];
	} else {
		$s = '';
	}
	$xmppStr = "{$_REQUEST['robotAddr']}|$ts|$cmd|$s|\n";
	socket_write($commandSocket, $xmppStr);	
	$json = socket_read($commandSocket, 1500);
//	echo "json is $json";
	$jArr = (array)json_decode($json);
	$alertMessage = getRobotMessage($_REQUEST['robotAddr']);
	if ($alertMessage != null)
	{
		$jArr['theMessage'] = $alertMessage;
	} else {
		$jArr['theMessage'] = '';
	}
	if (!isset($jArr['status'])) // there was no response from the server, so forge it
	{
		$jArr['status'] = 'timeout - no response from robot';
		$jArr['latency'] = microtime(true) - $latency;
	}
	$json = json_encode($jArr);
	 
	socket_set_nonblock($commandSocket);
	socket_close($commandSocket);
	list($phoneName, $domain) = preg_split("/@/", $_REQUEST['robotAddr']);
	$sql = "SELECT * FROM phones WHERE name=\"" . mysql_real_escape_string($phoneName) . "\" OR name=\"" . mysql_real_escape_string($_REQUEST['robotAddr']) . "\" ";
	$result = mysql_query($sql);
	$myrow = mysql_fetch_array($result);
	
	if (strlen($myrow["registration"]) > 0) {
		$phoneReg = $myrow["registration"];
		$deviceId = $myrow["deviceid"];
	}
	$a = $_REQUEST['robotAddr'];
	if ($cmd != 'u' || $cmd != 'n')
	{
		$speed = $s;
	} else {
		$speed = '';
	}
	$mtr = new messageToRobot($a, $a, $a, $cmd, $speed, '', $ts);
	$ts = $mtr->timeStamp;
	$sendStr = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $mtr->XML->asXML());

    syslog (LOG_NOTICE, "{$_REQUEST['robotAddr']}: Web interface message passed to controller: $sendStr");
	if (sendAndroidPush($sendStr, $_SESSION["auth_token"], $phoneReg))
	{
	    syslog (LOG_NOTICE, "{$_REQUEST['robotAddr']}: C2DM message passed to Google for device ID $deviceId: $sendStr");
	} else {
	    syslog (LOG_NOTICE, "{$_REQUEST['robotAddr']}: C2DM message FAILED for device ID $deviceId: $sendStr");
	}
	if (strlen($json))
	{
		return ($json);
	} else {
		return (false);
	} 
}

session_start();


	header("Content-type: text/json");
	if (isset ($_REQUEST['cmd']))
	{
		$json = robot_command($_REQUEST['cmd']);
	} 
	if (isset ($_REQUEST['pantilt']))
	{	
		$json = robot_command($_REQUEST['pantilt']);
	}
	header ("Content-type: text/json\n");
	if ($json !== false)
	{
		echo ($json);
	} else {
		echo (json_encode(array('status' => 'failed')));
	}

	// check for an existing auth token from google
	if (!isset($_SESSION["auth_token"]) || strlen($_SESSION["auth_token"]) < 5)
	{
		googleAuthenticate("telebotphone@gmail.com", "9thsense&");

	}
	
	// we have the phone id and the auth id, we're good to go!
	//echo "have auth already";

?>
