#!/usr/bin/env php
<?php
 
 // officebot-controller.php
 // created 2011-09-08 by alaina hardie, 9th sense robotics
 // updated 2012-05-22 by alaina hardie, 9th sense robotics
 // mountain view, ca

require_once('officebot-include.php'); 
require_once ('robotMessages.php');

// connect to XMPP server
$conn = new XMPPHP_XMPP('9thsense.com', 5222, 'controller', 
	'9thsense', 'xmpphp', '9thsense.com', true, XMPPHP_Log::LEVEL_ERROR);
$conn->connect();
$conn->autoSubscribe(true);
$conn->processUntil('session_start');
$conn->presence($status="Controller available.");

// create the socket listener that the web interface talks to
$commandSocket = socket_create (AF_INET, SOCK_STREAM, 0);
$r = socket_bind($commandSocket, '127.0.0.1', '49441');
$r = socket_listen($commandSocket,1000000);
socket_set_nonblock($commandSocket);

while (true) // PHP is smart about resource usage here
{
	if (($thisSock = @socket_accept($commandSocket)) !== false)
	{
		// we got a message, so now parse it
		$latency = microtime(true);
		$string = socket_read($thisSock, 1400, PHP_NORMAL_READ);
		/* 
		 four fields come in from the web interface. Check out officebot-controller.php.
		 they are the XMPP address of the robot ($a), the timestamp ($t), the command ($c), and 
		 the speed ($s), if any. 
		*/
		list($a, $t, $c, $s) = preg_split("/\|/", $string);
		
		$mtr = new messageToRobot($a, $a, $a, trim($c), trim($s), '', $t);
		$sendStr = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $mtr->XML->asXML());
		echo "$sendStr\n\n";
		// Send the XMPP message
		$conn->message($a, $sendStr);
	    syslog (LOG_NOTICE, "$a: Controller sent message to robot: $sendStr");
		
		$t=microtime(true);
		$payloads = $conn->processUntil('message', 1);
		
		// get the response from the robot
		foreach($payloads as $event) 
		{
			$pl = $event[1];
			list($from, $stuff) = split("/", $pl['from']);
			if($event[0] == 'message' && $from == $a) // parse all of the messages, and only act if it's a message from the robot you're talking to
			{
				// start to compose the response JSON that we're going to send back through the socket
				// to the web interface.
				$json['microDuration'] = microtime(true) - $t;
				$json['robot'] = $from;
			    syslog (LOG_NOTICE, "$from: Controller received response from robot: {$pl['body']}");
				
				$mfr = new messageFromRobot($pl['body']);
				$json['response'] = $mfr->responseValue;
				$json['version'] = "1.0";



				$json['status'] = 'ok';
				$json['latency'] = microtime(true) - $latency;
				$jsonStr = json_encode ($json);
				//print_r($robotVersionArray);
				//echo $jsonStr;
				
				// write a response to officebot-controller, which will close the socket after it receives it.
				socket_write($thisSock, $jsonStr);			
			} // endif message from this robot
		} // end foreach 
	} // end socket connection
} // end while (true)

?>
