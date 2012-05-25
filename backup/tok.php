<html>
<head>

<title>9th Sense</title>
<style type="text/css">
	div#nav-response {
		display: none;
	}
	div#container {
		width: 100%;
		margin-left: 150px;
		margin-top: 20px;
	}
	div#nav-reports {
		width: 25%;
		float: left;
		margin: 15px;
		padding: 10px;
	}
	div#nav-controls {
		width: 25%;
		float: left;
		margin: auto;
		padding: 10px;
		text-align: center;
	}
	div#pri-video {
		width: 25%;
		float: left;
		margin: 15px;
		padding: 10px;
	}
	p.nav-title {
		font-size: 32px;
		color: #bbbbbb;
	}	
	.centered {
		text-align: center;
	}
	img.control-button {
		width: 32;
		height: 32;
	}
	div.baseresult {
		font-size: 24px;
		font-family: Verdana, Helvetica;
	}	
	div.pantiltresult {
		font-size: 24px;
		font-family: Verdana, Helvetica;
	}	
	
</style>

<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.15.custom.min.js"></script>
<script type="text/javascript" src="js/shortcut.js"></script>
<script type="text/javascript">

driving_state = 'stopped';

function send_base_command (cmd)
{
	console.log('driving_state is ' + driving_state);	
//	if (cmd == 's' || cmd == 'x' || cmd == 'L' || cmd == 'R')
	if (cmd == 's' || cmd == 'x')
	{
		window.driving_state = 'stopped';
	}
	if (window.driving_state == 'driving' && (cmd != 's' || cmd != 'x'))
	{
		//console.log ('driving_state = ' + driving_state + ' and cmd = ' + cmd);
		console.log ('ignoring');
		return;
	}
//	if (cmd != 's' && cmd != 'x' && cmd != 'L' && cmd != 'R')
	if (cmd != 's' && cmd != 'x')
	{
		window.driving_state = 'driving';
	} else {
		window.driving_state = 'stopped';
	}
	console.log('driving_state is ' + driving_state);	
	$.post('officebot-controller.php?robotAddr=' + jQuery('#robotAddr').val() + '&cmd=' + cmd, function(data) {
	});
}

function send_pantilt_command (cmd)
{
	$.post('officebot-controller.php?robotAddr=' + jQuery('#robotAddr').val() + '&pantilt=' + cmd, function(data) {
	});
}

function pantilt_up()
{
	send_pantilt_command('u');
}

function pantilt_down()
{
	send_pantilt_command('d');
}

// function pantilt_left()
// {
// 	send_pantilt_command('l');
// }
// 
// function pantilt_right()
// {
// 	send_pantilt_command('r');
// }
// 
// function pantilt_center()
// {
// 	send_pantilt_command('j');
// }


function base_forward()
{
	send_base_command('F');
}

function base_backward()
{
	send_base_command('B');
}

function base_left()
{
	send_base_command('L');
}

function base_right()
{
	send_base_command('R');
}

function base_stop()
{
	send_base_command('x');
}

function keyboard_clear_shortcuts()
{
	shortcut.remove("w");
	shortcut.remove("a");
	shortcut.remove("s");
	shortcut.remove("d");
	shortcut.remove("x");
	shortcut.remove("Up");
	shortcut.remove("Down");
	shortcut.remove("Right");
	shortcut.remove("Left");
	shortcut.remove("Space");
}

function keyboard_on_keyup()
{	
	keyboard_clear_shortcuts();
	
	shortcut.add("w",function() {
		pantilt_up();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("a",function() {
		pantilt_left();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("s",function() {
		pantilt_down();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("d",function() {
		pantilt_right();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("x",function() {
		pantilt_center();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Up",function() {
		send_base_command('F');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Down",function() {
		send_base_command('F');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Right",function() {
		send_base_command('R');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Left",function() {
		send_base_command('L');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Space",function() {
		send_base_command('x');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
}
function keyboard_on_keydown_stop_on_keyup()
{	
	keyboard_clear_shortcuts();
	
	// pan/tilt is still done in increments, so currently no need to differentiate between
	// key down and key up
	shortcut.add("w",function() {
		pantilt_up();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("a",function() {
		pantilt_left();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("s",function() {
		pantilt_down();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("d",function() {
		pantilt_right();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("x",function() {
		pantilt_center();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	
	// For driving, key down initiates movement, so tie these to a keydown event

	shortcut.add("t",function() {
		send_base_command('F');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("g",function() {
		send_base_command('B');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("h",function() {
		send_base_command('R');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("f",function() {
		send_base_command('L');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});


	shortcut.add("Up",function() {
		send_base_command('F');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Down",function() {
		send_base_command('B');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Right",function() {
		send_base_command('R');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Left",function() {
		send_base_command('L');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Space",function() {
		send_base_command('x');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});

	shortcut.add("t",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("g",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("h",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("f",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	// In this form of driving, releasing the key stops the robot
	shortcut.add("Up",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Down",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Right",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("Left",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
}


	jQuery(document).ready(function() {
		// initialize with driving on keydown and stopping on keyup
		keyboard_on_keydown_stop_on_keyup(); 
		$("#button-pantilt-right").click(function() {
			pantilt_right();
		});		
		$("#button-pantilt-left").click(function() {
			pantilt_left();
		});
		$("#button-pantilt-down").click(function() {
			pantilt_down();
		});
		$("#button-pantilt-up").click(function() {
			pantilt_up();
		});
		$("#button-pantilt-center").click(function() {
			pantilt_center();
		});

		$("#button-base-right").click(function() {
			base_right();
		});
		$("#button-base-left").click(function() {
			base_left();
		});
		$("#button-base-forward").click(function() {
			base_forward();
		});
		$("#button-base-backward").click(function() {
			base_backward();
		});
		$("#button-base-stop").click(function() {
			base_stop();
		});
	 });
</script>

<!--<script src="https://apis.google.com/js/client.js?onload=onClientReady"></script>-->

<link href="css/ui-lightness/jquery-ui-1.8.15.custom.css" rel="stylesheet" type="text/css"/>


</head>
<body> 
 <div id="container">
	<div id="nav-controls">
		<div id="table-wrapper">
			<table style="float:left;" border="0">
				<tr>
					<th colspan="3">Navigation</th>
				</tr>
				<tr>
					<td class="centered" colspan="3"> <img alt="drive forward" id="button-base-forward" class="control-button" src="images/forward.png" /></td>
				</tr>
				<tr>
					<td class="centered"> <img alt="turn left" id="button-base-left" class="control-button" src="images/left.png" /></td>
					<td class="centered"> <img alt="stop moving" id="button-base-stop" class="control-button" src="images/stop.png" /></td>
					<td class="centered"> <img alt="turn right" id="button-base-right" class="control-button" src="images/right.png" /></td>
				</tr>
				<tr>
					<td class="centered" colspan="3"> <img alt="drive backward" id="button-base-backward" class="control-button" src="images/backward.png" /></td>
				</tr>
			</table>

			<table style=" margin-left: 30px; float:left;" border="0">
				<tr>
					<th colspan="3">Camera Tilt</th>
				</tr>
				<tr>
					<td class="centered" colspan="3"> <img alt="tilt up" id="button-pantilt-up" class="control-button" src="images/forward.png" /></td>
				</tr>
				<!--<tr>
					<td class="centered"> <img alt="pan left" id="button-pantilt-left" class="control-button" src="images/left.png" /></td>
					<td class="centered"> <img alt="center" id="button-pantilt-center" class="control-button" src="images/target.jpg" /></td>
					<td class="centered"> <img alt="pan right" id="button-pantilt-right" class="control-button" src="images/right.png" /></td>
				</tr>-->
				<tr>
					<td class="centered" colspan="3"> <img alt="tilt down" id="button-pantilt-down" class="control-button" src="images/backward.png" /></td>
				</tr>
			</table>

		</div>
		<div class="input">
			<!--<select id="robotAddr" name="robotAddr">
				<option value="litebot@9thsense.com">litebot@9thsense.com</option>
				<option value="droidbot@9thsense.com">droidbot@9thsense.com</option>
		</div>-->
		<input type="hidden" id="robotAddr" name="robotAddr" value="droidbot@9thsense.com" />
	</div>
<iframe id="videoEmbed" src="http://api.opentok.com/hl/embed/2emb99cdd927a98e1458221031e8702173229aeb" width="350" height="265" style="border:none" frameborder="0"></iframe>
	<div id="nav-response">
		<div class="baseresult"><span>Stopped</span></div><br />
		<!--<div class="pantiltresult">N/A</div><br />-->
	</div>

</div>


</body> 
</html>

</body>
</html>
