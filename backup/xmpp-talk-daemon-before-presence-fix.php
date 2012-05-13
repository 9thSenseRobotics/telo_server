#!/usr/bin/env php
<?php
 
 // officebot-controller.php
 // created 2011-09-08 by alaina hardie, 9th sense robotics
 // updated 2012-04-16 by alaina hardie, 9th sense robotics
 // mountain view, ca

require_once('officebot-include.php'); 

	//system ('rm -f /tmp/robot_commands.sock');
	$conn = new XMPPHP_XMPP('9thsense.com', 5222, 'controller', 
		'9thsense', 'xmpphp', '9thsense.com', true, XMPPHP_Log::LEVEL_VERBOSE);
	$conn->connect();
	$conn->processUntil('session_start');
	
	$commandSocket = socket_create (AF_INET, SOCK_STREAM, 0);
	$r = socket_bind($commandSocket, '127.0.0.1', '49440');
	$r = socket_listen($commandSocket,1000000);
	socket_set_nonblock($commandSocket);
	
	echo "Done... moving to loop";
	
	while (true)
	{
		if (($thisSock = @socket_accept($commandSocket)) !== false)
		{
			echo "got something\n";

			$string = socket_read($thisSock, 1400, PHP_NORMAL_READ);
			list($a, $c) = preg_split("/\|/", $string);
			$conn->message($a, $c);
			$t=microtime();
			echo "microtime is $t \n";
		}
	}

?>
