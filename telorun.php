<?php
 // the GET from teloui.php calls this page
 
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
    echo "authenticate started.";
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
    
    echo "curl done";

    if (strpos($response, '200 OK') === false) {
        return false;
    }

    echo "curl pass";
    echo $response;
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
    echo "sending push...";
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
    var_dump(curl_getinfo($ch,CURLINFO_HEADER_OUT)); //for debugging the request

    echo $post_fields;

    $response = curl_exec($ch);
    
    echo $response;
    
    
    curl_close($ch);
    echo "closed curl";
}

// ---- main code -----

// start session using a session cookie (this is how we save the auth token)
session_start();

echo "online";
// check for an existing auth token from google
if (!isset($_SESSION["auth_token"]) || strlen($_SESSION["auth_token"]) < 5)
{
    echo "getting auth...";
    googleAuthenticate("telebotphone@gmail.com", "9thsense&");
}

// check for what phone the user wants to use, this should be sent from the AJAX UI interface
if (!isset($_GET["phoneid"]))
{
    die("don't have phoneid");
}

// the payload is the string to send (should be sent from the AJAX UI interface)
if (!isset($_GET["payload"]) || strlen($_GET["payload"]) <= 0)
{
    die("didn't specify a payload.");
}

// do a lookup in the database against the phoneid and find the google key

// connect to the database
require_once("db.php");
connectDatabase();

$sql = "SELECT * FROM phones WHERE deviceid=\"" . mysql_real_escape_string($_GET["phoneid"]) . "\"";
echo $sql;
$result = mysql_query($sql);
$myrow = mysql_fetch_array($result);

if (strlen($myrow["registration"]) <= 0)
{
    die("that phone id didn't have a valid google key.");
} else {
    $phoneReg = $myrow["registration"];
}

// we have the phone id and the auth id, we're good to go!
//echo "have auth already";

echo "sending data.................<br><br>";

sendAndroidPush($_GET["payload"], $_SESSION["auth_token"], $phoneReg);

print_r($_SESSION);

?>


