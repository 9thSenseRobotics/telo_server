#!/usr/bin/env php
<?php
 
 // officebot-controller.php
 // created 2011-09-08 by alaina hardie, 9th sense robotics
 // updated 2012-05-10 by alaina hardie, 9th sense robotics
 // mountain view, ca

require_once('officebot-include.php'); 

	$conn = new XMPPHP_XMPP('9thsense.com', 5222, 'controller', 
		'9thsense', 'xmpphp', '9thsense.com', true, XMPPHP_Log::LEVEL_VERBOSE);
	$conn->connect();
	$conn->processUntil('session_start');
	
	$commandSocket = socket_create (AF_INET, SOCK_STREAM, 0);
	$r = socket_bind($commandSocket, '127.0.0.1', '49440');
	$r = socket_listen($commandSocket,1000000);
	socket_set_nonblock($commandSocket);
		
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
?>
