
driving_state = 'stopped';
canControl = true;

must_be_logged_in = 'You must be logged in to drive a robot. Please enter a valid username and password, click the "Log in" button, and try again.';
function unclick_buttons()
{
	jQuery(".control-button").removeClass('clicked');
	jQuery(".control-button").addClass('unclicked');
}
function send_base_command (cmd)
{
	if (jQuery('#robotAddr').val() == undefined)
	{
		alert (must_be_logged_in);
		return;
	}
	if (canControl == false)
	{
		return;
	}
	switch (cmd)
	{
		case 'F':
			jQuery("#button-base-forward").addClass('clicked');
			jQuery("#button-base-forward").removeClass('unclicked');
			break;
		case 'B':
			jQuery("#button-base-backward").addClass('clicked');
			jQuery("#button-base-backward").removeClass('unclicked');
			break;
		case 'L':
			jQuery("#button-base-left").addClass('clicked');
			jQuery("#button-base-left").removeClass('unclicked');
			break;
		case 'R':
			jQuery("#button-base-right").addClass('clicked');
			jQuery("#button-base-right").removeClass('unclicked');
			break;
		case 'x':
			jQuery("#button-base-stop").addClass('clicked');
			jQuery("#button-base-stop").removeClass('unclicked');
			unclick_buttons();
			break;
		default: 
			unclick_buttons();
			break;
	}
	console.log('driving_state is ' + driving_state);	
	if (cmd == 's' || cmd == 'x')
	{
		window.driving_state = 'stopped';
		unclick_buttons();
	}
	if (window.driving_state == 'driving' && (cmd != 's' || cmd != 'x'))
	{
		//console.log ('driving_state = ' + driving_state + ' and cmd = ' + cmd);
		console.log ('ignoring');
		return;
	}
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
	if (jQuery('#robotAddr').val() == '')
	{
		alert (must_be_logged_in);
		return;
	}
	if (canControl == false)
	{
		return;
	}
	switch (cmd)
	{
		case 'u':
			jQuery("#button-pantilt-up").addClass('clicked');
			jQuery("#button-pantilt-up").removeClass('unclicked');
			break;
		case 'n':
			jQuery("#button-pantilt-down").addClass('clicked');
			jQuery("#button-pantilt-down").removeClass('unclicked');
			break;
		case 'l':
			jQuery("#button-pantilt-left").addClass('clicked');
			jQuery("#button-pantilt-left").removeClass('unclicked');
			break;
		case 'r':
			jQuery("#button-pantilt-right").addClass('clicked');
			jQuery("#button-pantilt-right").removeClass('unclicked');
			break;
		case 'j':
			jQuery("#button-pantilt-center").addClass('clicked');
			jQuery("#button-pantilt-center").removeClass('unclicked');
			unclick_buttons();
			break;
		default: 
			unclick_buttons();
			break;
	}
	$.post('officebot-controller.php?robotAddr=' + jQuery('#robotAddr').val() + '&pantilt=' + cmd, function(data) {
		unclick_buttons();
	});
}

function pantilt_up()
{
	send_pantilt_command('u');
}

function pantilt_down()
{
	send_pantilt_command('n');
}

function pantilt_left()
{
	send_pantilt_command('l');
}

function pantilt_right()
{
	send_pantilt_command('r');
}

function pantilt_center()
{
	send_pantilt_command('j');
}


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
	shortcut.remove("W");
	shortcut.remove("A");
	shortcut.remove("S");
	shortcut.remove("D");
	shortcut.remove("X");
	shortcut.remove("i");
	shortcut.remove("j");
	shortcut.remove("k");
	shortcut.remove("l");
	shortcut.remove("m");
	shortcut.remove("I");
	shortcut.remove("J");
	shortcut.remove("K");
	shortcut.remove("L");
	shortcut.remove("M");
	shortcut.remove("Up");
	shortcut.remove("Down");
	shortcut.remove("Right");
	shortcut.remove("Left");
	shortcut.remove("Space");
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
	shortcut.add("W",function() {
		pantilt_up();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("A",function() {
		pantilt_left();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("S",function() {
		pantilt_down();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("D",function() {
		pantilt_right();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("X",function() {
		pantilt_center();
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	
	// For driving, key down initiates movement, so tie these to a keydown event

	shortcut.add("i",function() {
		send_base_command('F');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("k",function() {
		send_base_command('B');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("l",function() {
		send_base_command('R');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("j",function() {
		send_base_command('L');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("I",function() {
		send_base_command('F');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("K",function() {
		send_base_command('B');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("L",function() {
		send_base_command('R');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("J",function() {
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
	shortcut.add("m",function() {
		send_base_command('x');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});
	shortcut.add("M",function() {
		send_base_command('x');
	}, { 'type': 'keydown', 'propagate': false, 'disable_in_input': true});

	shortcut.add("i",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("k",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("l",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("j",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("I",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("K",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("L",function() {
		send_base_command('x');
	}, { 'type': 'keyup', 'propagate': false, 'disable_in_input': true});
	shortcut.add("J",function() {
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
		$( "#dialog-form" ).dialog({
			autoOpen: false,
			height: 390,
			width: 400,
			modal: true,
			close: function() {
				canControl = true;
				jQuery('#invitation-result').html('');
			}
		});
		$("#invite-form").submit(function(event){
			event.preventDefault();		
			$.get( '/telo-control?action=invite-driver&email=' + jQuery('#invite-email').val() + '&robot=' + jQuery('#robotAddr').val(),
				function( data ) {
					$("#invitation-result").empty().append( data );
				}
			);
		});
		$( "#settings-open" ).click (function(){
			canControl = false;
			$( "#dialog-form" ).dialog( "open" );
		});
		// initialize with driving on keydown and stopping on keyup
		keyboard_on_keydown_stop_on_keyup(); 

		$("#qConnect").click(function() {
			send_base_command('c');
		});		
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