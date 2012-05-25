#!/usr/bin/env php
<?php
 
 // xmpp-listen-daemon.php
 // created 2012-05-23 by alaina hardie, 9th sense robotics
 // updated 2012-05-23 by alaina hardie, 9th sense robotics
 // mountain view, ca

require_once('officebot-include.php'); 
require_once ('robotMessages.php');

function robotMessageToUser($robotAddress, $message)
// The next time a user sends a command to this robot--even the regular Ajax checkin--they will
// receive this message from the robot.
{
	$r = mysql_escape_string($robotAddress);
	$m = mysql_escape_string($message);

	$q = "	INSERT INTO 
				driving_message_from_robot
				(xmpp_username, message) VALUES ('$r', '$m')
			ON DUPLICATE KEY UPDATE message = '$m';";
  	$res = mysql_query($q);
	return true;
}

	$conn = new XMPPHP_XMPP('9thsense.com', 5222, 'receiver', 
		'9thsense', 'xmpphp', '9thsense.com', true, XMPPHP_Log::LEVEL_VERBOSE);
	$conn->connect();
	$conn->autoSubscribe(true);
	$conn->processUntil('session_start');
    $conn->presence($status="Receiver available.");
	echo "Available now.\n";
	
	while (true)
	{
		try {
			$payloads = $conn->processUntil('message', 1);
			foreach($payloads as $event) 
			{
				$pl = $event[1];
				var_dump($event);
				list($from, $stuff) = split("/", $pl['from']);
	
				$mfr = new messageFromRobot($pl['body']);
				$messageToUser = null;
				echo "MessageFromRobot responseValue is {$mfr->responseValue}\n";
				switch ($mfr->responseValue)
				{
					case 'a': // responds with "I'm alive"
						$commandArguments = 'alive';
						break;
					case 'm': // message to post to the interface the next time somebody makes an ajax request
						$messageToUser = $mfr->comment;
						break;
					default: 
						$commandArguments = $mfr->responseValue;
						break;
				}
				if ($messageToUser != null)
				{
					robotMessageToUser($from, $messageToUser);
					echo "Posting this message to the user: $messageToUser\n";
	
				} else {
					$mtr = new messageToRobot($receiverAddress, $receiverName, $from, '!', $commandArguments, '', microtime(true));
					$sendStr = $mtr->XML->asXML();
					$conn->message($from, $sendStr);
					echo "Replying with this message to robot $from: $sendStr\n";
				}
			} // end foreach
		} catch (Exception $e) {
			syslog (LOG_ERR, "xmpp-listen-daemon error: " . $e->getMessage() . ". From: $from, body {$pl['body']}");
		}
	} // end while (true)

?>
