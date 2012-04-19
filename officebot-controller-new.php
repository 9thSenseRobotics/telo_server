<?php

require_once ('officebot-include.php');

function is_valid_create_command($cmd)
// make sure it's a command we understand
{ 
	return ($cmd);
	
// 	switch ($cmd)
// 	{
// 		case 'i':
// 		case 'o':
// 		case 'F':
// 		case 'B':
// 		case 'c':
// 		case 'l':
// 		case 's':
// 		case 'x':
// 		case 'I':
// 		case 'O':
// 		case 'C':
// 		case 'L':
// 		case 'S':
// 		case 'X':
// 		case 'a':
// 		case 'A':
// 		case 'd':
// 		case 'D':
// 		case 'aa':
// 		case 'ddd':
// 			return ($cmd);
// 		default: 
// 			return (false);
// 	}
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
	global $conn, $createActionTable, $robotname;

	$commandSocket = socket_create (AF_INET, SOCK_STREAM, 0);
	socket_set_option($commandSocket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>2, "usec"=>500));
	socket_bind($commandSocket, '127.0.0.1');
	socket_connect($commandSocket, '127.0.0.1', '49440');
	socket_write($commandSocket, "{$_REQUEST['robotAddr']}|$cmd\n");	
	$json = socket_read($commandSocket, 1500);
	socket_close($commandSocket);
	if (strlen($json))
	{
		return ($json);
	} else {
		return (false);
	} 
}


function pantilt_up() 
{
	robot_command ('u');
}

function pantilt_down() 
{
	robot_command ('n');
}

function pantilt_left() 
{
	robot_command ('h');
}

function pantilt_right() 
{
	robot_command ('k');
}
function pantilt_center() 
{
	robot_command ('j');
}

function robot_stop() 
{
	robot_command('x');
}

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
		echo (json_encode('status' => 'failed'));
	
?>
