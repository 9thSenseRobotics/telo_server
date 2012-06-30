<?php

mysql_connect("localhost", "9thsense", "~FERmion") or die ("We are sorry, we have experienced database error 1043. Please email support@9thsense.com for assistance.");
mysql_select_db("9thsense") or die ("We are sorry, we have experienced database error 1151. Please email support@9thsense.com for assistance.");

openlog  ("9STC", LOG_ODELAY | LOG_PID, LOG_LOCAL0);

$robotname = "robot@9thsense.com";

$receiverAddress = "receiver@9thsense.com";
$receiverName = "Receiver";
$controllerAddress = "controller@9thsense.com";
$controllerName = 'Controller';

function getRobotMessage($xmppUsername)
{
	$x = mysql_escape_string($xmppUsername);
	$q = "	SELECT 
				message
			FROM 
				driving_message_from_robot
			WHERE
				xmpp_username = '$x'";
	$res = mysql_query($q);
	if (mysql_num_rows($res))
	{
		$r = mysql_fetch_array($res);
		$q = "	UPDATE 
					driving_message_from_robot
				SET 
					message = ''
				WHERE xmpp_username = '$x'";
		mysql_query($q);
		return ($r['message']);
	}
	return null;
}

require_once ('xmpphp/XMPP.php');
define ('CMD_SENSORS', 0x8e);
define ('SENSOR_GROUP_ALL', 0x00); // returns all sensor values
define ('SENSOR_GROUP_LENGTH', 52); 

$createPort = "/dev/ttyUSB0";
$arduinoPort = "/dev/ttyACM0";

$createActionTable = 'create_action';
$pantiltActionTable = 'pantilt_action';
$createStatusTable = 'create_status';

$timeout_time = 0.75; // timeout time in seconds

// $conn = mysql_connect($mysql_host, $mysql_user, $mysql_pass) or die;
// mysql_select_db ('officebot', $conn);
?>
