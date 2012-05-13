<?php

mysql_connect("localhost", "9thsense", "~FERmion") or die ("We are sorry, we have experienced database error 1043. Please email support@9thsense.com for assistance.");
mysql_select_db("9thsense") or die ("We are sorry, we have experienced database error 1151. Please email support@9thsense.com for assistance.");

$robotname = "robot@9thsense.com";

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
