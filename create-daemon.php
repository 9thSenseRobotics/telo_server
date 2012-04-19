<?php
 
 // officebot-controller.php
 // created 2011-09-08 by alaina hardie, 9th sense robotics
 // mountain view, ca

require_once('officebot-include.php'); 

function create_update_status ($fh)
// get the Create's status (battery capacity, etc.)
{
	global $conn, $createStatusTable;
	
	$sensorArray = array();
	
	fwrite ($fh, "\x80"); 
	usleep (1000);
	fwrite ($fh, "\x82"); 
	usleep (1000);
	fwrite ($fh, "\x8e");
	usleep (1000);
	fwrite ($fh, "\x06");
	fwrite ($fh, 0);
	usleep (1000);
	$bytestr = fread ($fh, 52);
	//echo strlen($bytestr) . "\n";
	if (strlen($bytestr) != 52)
	{	
	$finalArray['create_status'] = 'unavailable';
		
		return;
	}
	
	$s = unpack ("C*", $bytestr);
	
	$finalArray['bumpsAndWheelDrops'] = $s[1];
	$finalArray['wall'] = $s[2];
	$finalArray['cliff_left'] = $s[3];
	$finalArray['cliff_front_left'] = $s[4];
	$finalArray['cliff_front_right'] = $s[5];
	$finalArray['cliff_right'] = $s[6];
	$finalArray['virtual_wall'] = $s[7];
	$finalArray['lsd_wheel'] = $s[8];
	$finalArray['unused'] = ($s[9] << 8) & $s[10];
	$finalArray['infrared'] = $s[11];
	$finalArray['buttons'] = $s[12];
	$finalArray['distance'] = ($s[13] << 8) & $s[14];
	$finalArray['angle'] = ($s[15] << 8) & $s[16];
	$finalArray['charging_state'] = $s[17];
	$finalArray['voltage'] = ($s[18] << 8) & $s[19];
	$finalArray['current'] = ($s[20] << 8) & $s[21];
	$finalArray['battery_temperature'] = $s[22];
	$finalArray['battery_charge'] = ($s[23] << 8) & $s[24];
	$finalArray['battery_capacity'] = ($s[25] << 8) & $s[26];
	$finalArray['wall_signal'] = ($s[27] << 8) & $s[28];
	$finalArray['cliff_left_signal'] = ($s[29] << 8) & $s[30];
	$finalArray['cliff_front_left'] = ($s[31] << 8) & $s[32];	
	$finalArray['cliff_front_right'] = ($s[33] << 8) & $s[34];
	$finalArray['cliff_right'] = ($s[35] << 8) & $s[36];
	$finalArray['cargo_bay_digital_inputs'] = $s[37];
	$finalArray['cargo_bay_analog_signal'] = ($s[38] << 8) & $s[39];
	$finalArray['charging_sources_available'] = $s[40];
	$finalArray['oi_mode'] = $s[41];
	$finalArray['song_number'] = $s[42];
	$finalArray['song_playing'] = $s[43];
	$finalArray['number_of_stream_packets'] = $s[44];
	$finalArray['requested_velocity'] = ($s[45] << 8) & $s[46];
	$finalArray['requested_radius'] = ($s[47] << 8) & $s[48];
	$finalArray['requested_right_velocity'] = ($s[49] << 8) & $s[50];
	$finalArray['requested_left_velocity'] = ($s[51] << 8) & $s[52];
	$finalArray['create_status'] = 'available';
	
	$json_string = base64_encode(json_encode ($finalArray));
	
	//echo "$json_string\n";
	
	mysql_query ("update $createStatusTable set json_string =  '$json_string';");
	
}

function create_drive($fh, $vel, $rad)
{
	fwrite ($fh, "\x80"); 
	usleep (1000);
	fwrite ($fh, "\x82"); 
	usleep (1000);

    $vh = ($vel>>8)&0xff;
    $vl = ($vel&0xff);
    $rh = ($rad>>8)&0xff;
    $rl = ($rad&0xff);
    $str = sprintf ("\x89%c%c%c%c", $vh, $vl, $rh, $rl);
    fwrite($fh, $str); 
}
function create_forward($roomba) 
{
	global $current_state;
	$current_state = 'f';
	
    create_drive($roomba, 0x01f4, 0x8000); # 0x01f4= 200 mm/s, 0x8000=straight
}
function create_backward($roomba) 
{
	global $current_state;
	$current_state = 'b';
	
    create_drive($roomba, 0xff38, 0x8000); # 0xff38=-200 mm/s, 0x8000=straight
}
function create_left($roomba) 
{
	global $current_state;
	$current_state = 'l';
	
    create_drive($roomba, 0x01f4, 0x0001); # 0x01f4= 200 mm/s, 0x0001=spinleft
}
function create_right($roomba) 
{
	global $current_state;
	$current_state = 'r';
	
    create_drive($roomba, 0x01f4, 0xffff); # 0x01f4= 200 mm/s, 0xffff=spinright
}
function create_stop($roomba) 
{
	global $current_state;
	$current_state = 's';
	
    create_drive($roomba, 0x0000, 0x0000); # 0x01f4= 200 mm/s, 0xffff=spinright
}


$r = mysql_query("select * from $createActionTable");
// make sure there's only one row in the create action table
if (mysql_num_rows($r) != 1)
{
	mysql_query("delete from $createActionTable");
	mysql_query("insert into $createActionTable (command, last_update) values ('s', " . microtime(true) . ");");
}

$r = mysql_query("select * from $pantiltActionTable");
// make sure there's only one row in the pantilt action table
if (mysql_num_rows($r) != 1)
{
	mysql_query("delete from $pantiltActionTable");
	mysql_query("insert into $pantiltActionTable (command, last_update) values ('s', " . microtime(true) . ");");
}



system ("stty -F $arduinoPort 9600 raw -parenb -parodd cs8 -hupcl -cstopb clocal");
$arduinoFp = fopen ($arduinoPort, 'w+');
if (!$arduinoFp)
{
	echo (json_encode(array('ret' => $arduinoPort . ' controller open failed' )));
	exit;
}

fwrite($arduinoFp, 'y');
sleep (1);
system ("stty -F $createPort 57600 raw -parenb -parodd cs8 -hupcl -cstopb clocal");
if (!$createFp = fopen ($createPort, 'w+'))
{
	echo (json_encode(array('ret' => $createPort . ' base open failed')));
	exit;
}

$current_state = 's';

// one infinite loop (ha ha, get it?)
$count = 0;
while (1)
{
	$count ++;
	if ($count % 30)
	{
		create_update_status ($createFp);
	}
	$createResult = mysql_query("select * from $createActionTable");
	if (mysql_num_rows($createResult) != 1) // epic fail. too many (or too few) rows.
	{
		syslog(LOG_NOTICE, "Incorrect number of rows in $createActionTable: " .mysql_num_rows()); 
		exit;
	}
	$createArray = mysql_fetch_assoc($createResult); // get the row

	$elapsed_time = microtime(true) - $createArray['last_update'];
	
	//echo microtime(true) . " {$createArray['last_update']} $timeout_time " . $elapsed_time . "\n"; 
	//echo "{$createArray['command']}, {$createArray['last_update']}, $elapsed_time\n";
	if ($elapsed_time > $timeout_time)
	{
		// we've gone too long without an update, so send a stop command.
		create_stop($createFp);
		//echo "stopping\n";
		//mysql_query("update $createActionTable set command =  's', last_update = " . microtime (true)); 

	} else {
		// we're within the timeout period, so do what we're telling you.
		switch ($createArray['command'])
		{
			case 'f': // move it forward
				create_forward($createFp);
				break;
			case 'b': // back it on up
				create_backward($createFp);
				break;
			case 'l': // turn to the left
				create_left($createFp);
				break;
			case 'r': // turn to the right
				create_right($createFp);
				break;
			case 's': // stop movement
				create_stop($createFp);
				break;
		} // endswitch
		//echo $createArray['command'] . "\n";
	} // endif

	$arduinoResult = mysql_query("select * from $pantiltActionTable");
	if (mysql_num_rows($arduinoResult) != 1) // epic fail. too many (or too few) rows.
	{
		syslog(LOG_NOTICE, "Incorrect number of rows in $pantiltActionTable: " .mysql_num_rows()); 
		exit;
	}
	$arduinoArray = mysql_fetch_assoc($arduinoResult); // get the row

	if ($arduinoArray['command'] != "x") 
	// if it's anything other than "stay", it's a movement command, so update the row to clear that
	{
		echo $arduinoArray['command'];
		switch ($arduinoArray['command'])
		{
			case 'l': // pan left
				$acmd = 'a';
				break;
			case 'r': // pan right
				$acmd = 'd';
				break;
			case 'u': // tilt up
				$acmd = 'w';
				break;
			case 'd': // tilt down
				$acmd = 's';
				break;
			default:
				$acmd = NULL;
				break;
		}
		echo $acmd;
		fwrite($arduinoFp, $acmd . $acmd . $acmd, 3);
		mysql_query ("update $pantiltActionTable SET command = 'x'");
		usleep (500);
	}
} // endwhile
?>
