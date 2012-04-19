#!/usr/bin/env php
<?php
 
 // officebot-controller.php
 // created 2011-09-08 by alaina hardie, 9th sense robotics
 // mountain view, ca

require_once('officebot-include.php'); 

// function create_drive($fh, $vel, $rad)
// {
// 	fwrite ($fh, "\x80"); 
// 	usleep (1000);
// 	fwrite ($fh, "\x82"); 
// 	usleep (1000);
// 
//     $vh = ($vel>>8)&0xff;
//     $vl = ($vel&0xff);
//     $rh = ($rad>>8)&0xff;
//     $rl = ($rad&0xff);
//     $str = sprintf ("\x89%c%c%c%c", $vh, $vl, $rh, $rl);
//     fwrite($fh, $str); 
// }
// function create_forward($roomba) 
// {
// 	global $current_state;
// 	$current_state = 'f';
// 	
//     create_drive($roomba, 0x01f4, 0x8000); # 0x01f4= 200 mm/s, 0x8000=straight
// }
// function create_backward($roomba) 
// {
// 	global $current_state;
// 	$current_state = 'b';
// 	
//     create_drive($roomba, 0xff38, 0x8000); # 0xff38=-200 mm/s, 0x8000=straight
// }
// function create_left($roomba) 
// {
// 	global $current_state;
// 	$current_state = 'l';
// 	
//     create_drive($roomba, 0x01f4, 0x0001); # 0x01f4= 200 mm/s, 0x0001=spinleft
// }
// function create_right($roomba) 
// {
// 	global $current_state;
// 	$current_state = 'r';
// 	
//     create_drive($roomba, 0x01f4, 0xffff); # 0x01f4= 200 mm/s, 0xffff=spinright
// }
// function create_stop($roomba) 
// {
// 	global $current_state;
// 	$current_state = 's';
// 	
//     create_drive($roomba, 0x0000, 0x0000); # 0x01f4= 200 mm/s, 0xffff=spinright
// }
// 
// 
// $r = mysql_query("select * from $createActionTable");
// // make sure there's only one row in the create action table
// if (mysql_num_rows($r) != 1)
// {
// 	mysql_query("delete from $createActionTable");
// 	mysql_query("insert into $createActionTable (command, last_update) values ('s', " . microtime(true) . ");");
// }
// 
// $r = mysql_query("select * from $pantiltActionTable");
// // make sure there's only one row in the pantilt action table
// if (mysql_num_rows($r) != 1)
// {
// 	mysql_query("delete from $pantiltActionTable");
// 	mysql_query("insert into $pantiltActionTable (command, last_update) values ('s', " . microtime(true) . ");");
// }

	//system ('rm -f /tmp/robot_commands.sock');
	$conn = new XMPPHP_XMPP('9thsense.com', 5222, 'controller', 
		'9thsense', 'xmpphp', '9thsense.com', true, XMPPHP_Log::LEVEL_VERBOSE);
	$conn->connect();
	$conn->processUntil('session_start');
	
	$commandSocket = socket_create (AF_INET, SOCK_STREAM, 0);
	$r = socket_bind($commandSocket, '127.0.0.1', '49440');
	echo "Bind: $r";
	$r = socket_listen($commandSocket,1000000);
	echo "listen: $r";
	socket_set_nonblock($commandSocket);
	echo "set_nonblock: $r";
	
	echo "Done... moving to loop";
	
	while (true)
	{
		if (($thisSock = @socket_accept($commandSocket)) !== false)
		{
			$string = socket_read($thisSock, 1400, PHP_NORMAL_READ);
			//$string = "litebot@9thsense.com|" . time() . "|$string";
			list($a, $c) = preg_split("/\|/", $string);
			$conn->message($a, $c);
			echo "\nthis is it: $string, $a, $c\n";
		}
	}

// $current_state = 's';
// 
// // one infinite loop (ha ha, get it?)
// $count = 0;
// while (1)
// {
// 	$count ++;
// 	if ($count % 30)
// 	{
// 		//create_update_status ($createFp);
// 	}
// 	$createResult = mysql_query("select * from $createActionTable");
// 	if (mysql_num_rows($createResult) != 1) // epic fail. too many (or too few) rows.
// 	{
// 		syslog(LOG_NOTICE, "Incorrect number of rows in $createActionTable: " .mysql_num_rows()); 
// 		exit;
// 	}
// 	$createArray = mysql_fetch_assoc($createResult); // get the row
// 
// 	$createResult = mysql_query("select * from $createActionTable");
// 	if (mysql_num_rows($createResult) != 1) // epic fail. too many (or too few) rows.
// 	{
// 		syslog(LOG_NOTICE, "Incorrect number of rows in $createActionTable: " .mysql_num_rows()); 
// 		exit;
// 	}
// 
// 	$elapsed_time = microtime(true) - $createArray['last_update'];
// 	
// 	//echo microtime(true) . " {$createArray['last_update']} $timeout_time " . $elapsed_time . "\n"; 
// 	//echo "{$createArray['command']}, {$createArray['last_update']}, $elapsed_time\n";
// 	if ($elapsed_time > $timeout_time)
// 	{
// 		// we've gone too long without an update, so send a stop command.
// 		$conn->message('alainahardie@gmail.com', 's');
// 
// 		//echo "stopping\n";
// 		//mysql_query("update $createActionTable set command =  's', last_update = " . microtime (true)); 
// 
// 	} else {
// 		// we're within the timeout period, so do what we're telling you.
// 		switch ($createArray['command'])
// 		{
// 			case 'f': // move it forward
// 				$conn->message('alainahardie@gmail.com', 'f');
// 				break;
// 			case 'b': // back it on up
// 				$conn->message('alainahardie@gmail.com', 'b');
// 				break;
// 			case 'l': // turn to the left
// 				$conn->message('alainahardie@gmail.com', 'l');
// 				break;
// 			case 'r': // turn to the right
// 				$conn->message('alainahardie@gmail.com', 'r');
// 				break;
// 			case 's': // stop movement
// 				$conn->message('alainahardie@gmail.com', 's');
// 				break;
// 		} // endswitch
// 		echo $createArray['command'] . "\n";
// 	} // endif
// 
// 	$arduinoResult = mysql_query("select * from $pantiltActionTable");
// 	if (mysql_num_rows($arduinoResult) != 1) // epic fail. too many (or too few) rows.
// 	{
// 		syslog(LOG_NOTICE, "Incorrect number of rows in $pantiltActionTable: " .mysql_num_rows()); 
// 		exit;
// 	}
// 	$arduinoArray = mysql_fetch_assoc($arduinoResult); // get the row
// 
// 	if ($arduinoArray['command'] != "x") 
// 	// if it's anything other than "stay", it's a movement command, so update the row to clear that
// 	{
// 		echo $arduinoArray['command'];
// 		switch ($arduinoArray['command'])
// 		{
// 			case 'l': // pan left
// 				$acmd = 'a';
// 				break;
// 			case 'r': // pan right
// 				$acmd = 'd';
// 				break;
// 			case 'u': // tilt up
// 				$acmd = 'w';
// 				break;
// 			case 'd': // tilt down
// 				$acmd = 's';
// 				break;
// 			default:
// 				$acmd = NULL;
// 				break;
// 		}
// 		echo $acmd;
// 		fwrite($arduinoFp, $acmd . $acmd . $acmd, 3);
// 		mysql_query ("update $pantiltActionTable SET command = 'x'");
// 		usleep (500);
// 	}
// } // endwhile
?>
