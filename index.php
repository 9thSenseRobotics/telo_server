<?php
	// set up swiftmailer
	require ('Swift-4.1.7/lib/swift_required.php');
	$transporter = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
	  ->setUsername('mailer@9thsense.com')
	  ->setPassword('fmer!13rmERKM');
	$mailer = Swift_Mailer::newInstance($transporter);

	mysql_connect("localhost", "9thsense", "~FERmion") or die ("We are sorry, we have experienced database error 1043. Please email support@9thsense.com for assistance.");
	mysql_select_db("9thsense") or die ("We are sorry, we have experienced database error 1151. Please email support@9thsense.com for assistance.");
	$user = array();
	$errorClass = '';
	$showCreateDialog = false;
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'create-account')
	{
		$k = $_REQUEST['key'];
		$q = "
				SELECT 
					* 
				FROM
					tz_members
				WHERE
					registrationkey = '" . mysql_escape_string($k) . "' 
			";
		$res = mysql_query($q);
		if (mysql_num_rows($res) == 1)
		{
			$showCreateDialog = true;	
			$user = mysql_fetch_assoc($res);
		} else if (mysql_num_rows($res) > 1) {
			echo 'Database error - multiple users found. Request not sent. We have notified support automatically.';
			mail ("alaina@9thsense.com", "Multiple users/keys found", "Key $k");
			exit;
		} else {
			echo 'Database error - no user found. We have notified support automatically. Sorry, but we cannot continue';
			mail ("alaina@9thsense.com", "No users/keys found: ", "No users/keys found: key $k");
			exit;
		}
	}
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'invite-driver')
	{
		$e = $_REQUEST['email'];
		$r = $_REQUEST['robot'];
		$q = "
				SELECT 
					id 
				FROM
					tz_members
				WHERE
					usr = '" . mysql_escape_string($r) . "' AND
					email = '" . mysql_escape_string($e) . "' 
			";
		$res = mysql_query($q);
		if (mysql_num_rows($res) == 1)
		{
			echo "$e already has access to drive $r.";
			exit;
		} else if (mysql_num_rows($res) > 1) {
			echo 'Database error - multiple users found. Request not sent. We have notified support automatically.';
			mail ("alaina@9thsense.com", "Multiple users found: email $e, robot $r", "Multiple users found: email $e, robot $r");
			exit;
		} else {
			$q = "
				INSERT INTO
					tz_members
				(email, usr, registrationkey) 
				VALUES
				('" . mysql_escape_string($e) . "',
				'" . mysql_escape_string($r) . "',
				'" . md5($e . $r) . "')
			";
			//echo $q;
			$res = mysql_query($q);
			$headers = 'From: 9th Sense Invitation <support@9thsense.com>' . "\r\n" .
			'Reply-To: support@9thsense.com';
			$msg = 
			 "
Hi there,

You have been invited to drive a 9th Sense robot.

To register, click the link below, or paste it into a browser:

http://9thsense.com/t/?action=create-account&key=" . md5($e. $r) . "

If you have any problems, please don't hesitate to contact 9th Sense Tech Support at support@9thsense.com.

Thanks!
The 9th Sense Team
";
			$message = Swift_Message::newInstance()
			
			  // Give the message a subject
			  ->setSubject('Your invitation to drive a 9th Sense robot')
			
			  // Set the From address with an associative array
			  ->setFrom(array('support@9thsense.com' => '9th Sense'))
			
			  // Set the To addresses with an associative array
			  ->setTo(array($e => '$e'))
			
			  // Give it a body
			  ->setBody($msg);
  
			if ($mailer->send($message))
			{
				echo "Invitation sent to $e.";
			 } else {
				echo "Failed to send invitation to $e.";
			 }
		}
		exit;
	}
	function check_login ($email, $passwd)
	// check the username/password combination. $passwd should be md5'd just like it is in the database.
	{
		global $loginStatus, $user;
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'changepw')
		{
			$q = "
				UPDATE
					tz_members
				SET
					pass = '" . mysql_escape_string($passwd) . "',
					registrationkey = ''
				WHERE 
					email = '" . mysql_escape_string($email) . "'
			";
			//echo $q;
			$r = mysql_query($q);
		}
		$q = "
							SELECT
								* 
							FROM 
								tz_members 
							WHERE
								email = '" . mysql_escape_string($email) . "' AND
								pass = '" . mysql_escape_string($passwd) . "'
								";
		$r = mysql_query($q);
		if (mysql_num_rows($r) == 1)
		{
			$user = mysql_fetch_assoc($r);
			setcookie("HeloUsername", $user['email']);
			setcookie("HeloPassword", $passwd);
			return true;
		} else {
			setcookie("HeloUsername", '');
			setcookie("HeloPassword", '');
			$user = array();
			return false;
		}			
	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<title>9th Sense</title>
		<link rel="stylesheet" href="css/tc.css" />
		<script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.15.custom.min.js"></script>
		<script type="text/javascript" src="js/shortcut.js"></script>
		<script type="text/javascript" src="js/tc.js"></script>
		<script type="text/javascript">
		</script>
		<link href="css/ui-darkness/jquery-ui-1.8.19.custom.css" rel="stylesheet" />
	</head>
	<body> 
		<div id="header">
			<div class="left-side">
				<div class="header-title">
					<a target="_blank" href="http://9thsense.com"><img class="9thsense-logo" height="15" src="http://9thsense.com/uploads/2012/01/logo_new.png" /></a>
				 	<a target="_blank" href="http://9thsense.com">Helo - Your Personal Avatar</a>
				 </div>
			</div> <!-- .left-side -->
			<div class="right-side">
				<?
					if (isset($_REQUEST['email']) && isset($_REQUEST['password']))
					{
						if (check_login($_REQUEST['email'], md5($_REQUEST['password'])))
						{
					?>
							<div class="header-username">
							Welcome, <?= $user['email'] ?>
							
							</div>
							<div id="settings-open" class="header-settings"><a href="#">Settings / Invitations</a></div>
							<input type="hidden" id="robotAddr" name="robotAddr" value="<?= $user['usr'] ?>" />
							<div class="header-logout"><a href="?logout">Log out</a></div>
					<?
						} else {
							if (isset($_REQUEST['email']) || isset($_REQUEST['password']))
							{
								$errorClass = ' class="error" ';
							} else {
								$errorClass = '';
							}
							?>
									<form action="#" method="post">
										<div class="header-login">
											<span class="invalid">Invalid login. Please try again.</span>
											Username: <input type="text" size="20" maxlength="50" name="email" <?= $errorClass ?> />
											Password: <input type="password" size="15" maxlength="50" name="password" <?= $errorClass ?> />
											<input type="submit" value="Log in" />
										</div>
									</form>
							<?
						}						
					} else { 
					?>
							<form action="#" method="post">
								<div class="header-login">
									Username: <input type="text" size="20" maxlength="50" name="email"  />
									Password: <input type="password" size="15" maxlength="50" name="password" />
									<input type="submit" value="Log in" />
								</div>
							</form>
					<? } ?>
			</div> <!-- .right-side -->
		</div><!-- #header -->
		 <div id="container">
			<div id="table-wrapper">
				<div class="commandbox">
					<div class="commandbox-title">Keyboard commands</div>
					<div class="control-title">Driving</div>
					<ul>
						<li>Go forward - Up arrow, "I"</li>
						<li>Go backward - Down arrow, "K"</li>
						<li>Turn left - Left arrow, "J"</li>
						<li>Turn right - Right arrow, "L"</li>
						<li>Stop - Space bar, "M"</li>
					</ul>
					<div class="control-title">Camera tilt</div>
					<ul>
						<li>Tilt up - "W"</li>
						<li>Tilt down - "S"</li>
					</ul>
				</div>
				<div class="subtable">
					<div class="control-title">Driving</div>
					<table id="navigation-table" border="0">
						<tr>
							<td class="centered" colspan="3"> <img alt="drive forward" id="button-base-forward" class="control-button unclicked up" src="images/img_trans.gif" /></td>
						</tr>
						<tr>
							<td class="centered"> <img alt="turn left" id="button-base-left" class="control-button unclicked left" src="images/img_trans.gif" /></td>
							<td class="centered"> <img alt="stop moving" id="button-base-stop" class="control-button unclicked stop" src="images/img_trans.gif" /></td>
							<td class="centered"> <img alt="turn right" id="button-base-right" class="control-button unclicked right" src="images/img_trans.gif" /></td>
						</tr>
						<tr>
							<td class="centered" colspan="3"> <img alt="drive backward" id="button-base-backward" class="control-button unclicked down" src="images/img_trans.gif" /></td>
						</tr>
						<? //if ($user['email'] == 'helo.five@9thsense.com') { ?>
						<tr>
							<td class="centered" colspan="3">
								<div class="control-title">Speed:</div> 
								<div id="speed-slider"></div>
								<div class="slider-labels">
								<span class="float-left">Slow</span>
								<span class="float-right">Fast</span>
								<div class="set-to-default"><a id="set-slider-default" href="#">Default</a></div>
								</div>
							</td>
						</tr>
						<?//}?>
					</table>
				</div> <!-- end div .subtable -->
				<div class="subtable">
					<div class="control-title">Camera tilt</div>
					<table id="pantilt-table" border="0">
						<tr>
							<td class="centered" colspan="3"> <img alt="tilt up" id="button-pantilt-up" class="control-button unclicked up" src="images/img_trans.gif" /></td>
						</tr>
						<tr>
							<td class="centered" colspan="3"> <img alt="tilt down" id="button-pantilt-down" class="control-button unclicked down" src="images/img_trans.gif" /></td>
						</tr>
					</table>
				</div> <!-- end div .subtable -->
				<div class="subtable">
					<div class="control-title">Command status</div>
					<table id="message-table" border="0">
						<tr>
							<td class="left-align"><strong>Command latency:</strong> <span id="latency">0</span> seconds</td>
						</tr>
						<tr>
							<td class="left-align"><strong>Last status:</strong> <span id="status">ok</span></td>
						</tr>
						<tr class="message-row">
							<td class="left-align"><strong>Messages:</strong> <span id="message"></span><br/>
							<a class="clear-link" href="#">(Clear messages)</a></td>
						</tr>
					</table>
				</div> <!-- end div .subtable -->
			</div><!-- end div #table-wrapper -->
		</div> <!-- end div #container -->
	<div id="dialog-form" title="Update settings">
		<form id="password-form" action="/tc2/index.php?action=changepw" method="post">
			<h2>Change your password</h2>
			<label for="new-password">New password</label>
			<input type="password" name="password" id="new-password" class="text ui-widget-content ui-corner-all" /><br /> <br />
			<input type="hidden" name="action" value="changepw" />
			<input type="hidden" name="email" value="<?=$user['email']?>" />
			<div class="right">
				<input type="submit" value="Save password" />
			</div>
		</form>
		<hr width="100%" />
		<form id="invite-form" action="/" method="post">
			<h2>Invite a new driver</h2>
			<em>Enter the email of the person you wish to invite and click "Send invitation."</em><br /> <br />
			<label for="invite-email">Email</label>
			<input type="text" name="invite-email" id="invite-email" class="text ui-widget-content ui-corner-all" /><br /> <br />
			<input type="hidden" name="action" value="invite-driver" />
			<div class="right">
				<input type="submit" value="Send invitation" />
			</div>
			<div id="invitation-result"></div>
		</form>
	</div>	 <!-- end div #dialog-form -->


	<? 
		if ($showCreateDialog)
		{
	?>
	<script	type="text/javascript">
		jQuery(document).ready(function() {
			$( "#dialog-create" ).dialog({
				autoOpen: true,
				height: 390,
				width: 400,
				modal: true,
			   closeOnEscape: false,
			   open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog).hide(); }
			});
		});
	</script>
	<div id="dialog-create" title="Choose a password">
		<form id="password-form" action="/tc2/index.php?action=changepw" method="post">
			<h2>Choose your password</h2>
			<label for="new-password">New password</label>
			<input type="password" name="password" id="new-password" class="text ui-widget-content ui-corner-all" /><br /> <br />
			<input type="hidden" name="action" value="changepw" />
			<input type="hidden" name="email" value="<?=$user['email']?>" />
			<div class="right">
				<input type="submit" value="Save password" />
			</div>
		</form>
	</div>	 <!-- end div #dialog-create -->
	<? } ?>



	</body> 
</html>