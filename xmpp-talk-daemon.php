#!/usr/bin/env php
<?php
 
 // officebot-controller.php
 // created 2011-09-08 by alaina hardie, 9th sense robotics
 // updated 2012-05-22 by alaina hardie, 9th sense robotics
 // mountain view, ca

require_once('officebot-include.php'); 
require_once ('robotMessages.php');

	$conn = new XMPPHP_XMPP('9thsense.com', 5222, 'controller', 
		'9thsense', 'xmpphp', '9thsense.com', true, XMPPHP_Log::LEVEL_ERROR);
	$conn->connect();
	$conn->autoSubscribe(true);
	$conn->processUntil('session_start');
    $conn->presence($status="Controller available.");
	
	$commandSocket = socket_create (AF_INET, SOCK_STREAM, 0);
	$r = socket_bind($commandSocket, '127.0.0.1', '49441');
	$r = socket_listen($commandSocket,1000000);
	socket_set_nonblock($commandSocket);
	
	while (true)
	{
		if (($thisSock = @socket_accept($commandSocket)) !== false)
		{
			echo "got something\n";
			$latency = microtime(true);
			$string = socket_read($thisSock, 1400, PHP_NORMAL_READ);
			list($a, $t, $c, $s) = preg_split("/\|/", $string);
			if (!isset($robotVersionArray[$a]))
			{
				$robotVersionArray[$a] = "0.9";
			}
			
			switch ($robotVersionArray[$a])
			{
				case "0.9":
					$sendStr = $c;
					break;
				case "1.0":
				default:
					$mtr = new messageToRobot($a, $a, $a, trim($c), trim($s), '', $t);
					$sendStr = $mtr->XML->asXML();
					break;
			}
			
			$conn->message($a, $sendStr);
			$t=microtime();
			$payloads = $conn->processUntil('message', 1);
			foreach($payloads as $event) 
			{
				$pl = $event[1];
				list($from, $stuff) = split("/", $pl['from']);
				echo "in foreach: $a, with from = $from, event[0] = {$event[0]}\n";
				if($event[0] == 'message' && $from == $a) 
				{
					$json['microDuration'] = microtime() - $t;
					$json['robot'] = $from;
					if (preg_match("/\</",$pl['body']))
					{
						$robotVersionArray[$a] = '1.0';
						try {
							$mfr = new messageFromRobot($pl['body']);
							$json['response'] = $mfr->responseValue;
						} catch (Exception $e) {
							echo "Could not construct messageFromRobot from body:" . $e->getMessage();
						}
					} else {
						$robotVersionArray[$a] = '0.9';
						$json['response'] = $pl['body'];
					}
					$json['status'] = 'ok';
					$json['version'] = $robotVersionArray[$a];
					$json['latency'] = microtime(true) - $latency;
					$jsonStr = json_encode ($json);
					//print_r($robotVersionArray);
					echo $jsonStr;
					socket_write($thisSock, $jsonStr);			
				}
			}
		}
	}

?>
