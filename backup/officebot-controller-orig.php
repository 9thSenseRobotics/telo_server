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

function is_valid_pantilt_command($cmd)
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
	socket_bind($commandSocket, '127.0.0.1');
	socket_connect($commandSocket, '127.0.0.1', '49440');
	socket_write($commandSocket, "{$_REQUEST['robotAddr']}|$cmd\n");	
	socket_close($commandSocket);
// 	if (is_valid_create_command($cmd))
// 	{
// 		mysql_query("update $createActionTable set command =  '$cmd', last_update = " . microtime (true)); 
// 	} else {
// 		syslog (LOG_NOTICE, "invalid robot command issued: $cmd");
// 	}
}

function pantilt_command($cmd)
{
	robot_command($cmd);
}

function pantilt_up() 
{
	pantilt_command ('u');
}

function pantilt_down() 
{
	pantilt_command ('n');
}

function pantilt_left() 
{
	pantilt_command ('h');
}

function pantilt_right() 
{
	pantilt_command ('k');
}
function pantilt_center() 
{
	pantilt_command ('j');
}



// function robot_forward() 
// {
// 	robot_command('o');
// }
// 
// function robot_backward() 
// {
// 	robot_command('l');
// }
// function robot_left() 
// {
// 	robot_command('aa');
// }
// function robot_right() 
// {
// 	robot_command('dd');
// }
// function robot_stop() 
// {
// 	robot_command('x');
// }

// function robot_forward_small() 
// {
// 	robot_command('t');
// }
// 
// function robot_backward_small() 
// {
// 	robot_command('g');
// }
// 
// 
// function robot_forward() 
// {
// 	robot_command('f');
// }
// 
// function robot_backward() 
// {
// 	robot_command('b');
// }
// 
// function robot_forward_forever() 
// {
// 	robot_command('F');
// }
// 
// function robot_backward_forever() 
// {
// 	robot_command('B');
// }
// 
// 
// function robot_left() 
// {
// 	robot_command('L');
// }
// function robot_right() 
// {
// 	robot_command('R');
// }
// 
// function robot_left_small() 
// {
// 	robot_command('l');
// }
// function robot_right_small() 
// {
// 	robot_command('r');
// }


function robot_stop() 
{
	robot_command('x');
}

	header("Content-type: text/json");
	if (isset ($_REQUEST['cmd']))
	{
		robot_command($_REQUEST['cmd']);
		$retval = $_REQUEST['cmd'];
	} 
	if (isset ($_REQUEST['pantilt']))
	{	
		switch ($_REQUEST['pantilt'])
		{
			case 'l': // pan left
				pantilt_left();
				break;
			case 'r': // pan right
				pantilt_right();
				break;
			case 'u': // tilt up
				pantilt_up();
				break;
			case 'd': // tilt down
				pantilt_down();
				break;
			case 'j': // center
				pantilt_center();
				break;
			default:
				$cmd = NULL;
				break;
		}
	}
	header ("Content-type: text/plain\n");
	echo (json_encode(array('commandSent' => isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : $_REQUEST['pantilt'] )));
?>
