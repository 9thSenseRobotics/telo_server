package oculus;

public enum PlayerCommands {

	// all valid commands
	publish, move, nudge, slide, dockgrab, framegrab, battstats, restart, docklineposupdate, autodock, autodockcalibrate, 
	speech, getdrivingsettings, drivingsettingsupdate, gettiltsettings, cameracommand, tiltsettingsupdate, 
	tilttest, speedset, dock, relaunchgrabber, clicksteer, chat, statuscheck, systemcall, streamsettingsset, 
	streamsettingscustom, motionenabletoggle, playerexit, playerbroadcast, password_update, 
	new_user_add, user_list, delete_user, extrauser_password_update, username_update, 
	disconnectotherconnections, showlog, monitor, assumecontrol, softwareupdate, 
	arduinoecho, arduinoreset, setsystemvolume, beapassenger, muterovmiconmovetoggle, spotlightsetbrightness, 
	floodlight, writesetting, holdservo, opennisensor, videosoundmode;

	// sub-set that are restricted to "user0"
	public enum AdminCommands {

		// TODO: CHECK THESE
		new_user_add, user_list, delete_user, extrauser_password_update, restart, disconnectotherconnections, showlog, softwareupdate, relaunchgrabber, systemcall
	}

	/**
	 * @return true if given command is in the sub-set
	 */
	public static boolean requiresAdmin(PlayerCommands cmd) {
		for (AdminCommands admin : AdminCommands.values()) {
			if (admin.equals(cmd))
				return true;
		}

		return false;
	}

	/**
	 * @return true if given command is in the sub-set
	 */
	public boolean requiresAdmin() {
		for (AdminCommands admin : AdminCommands.values()) {
			if (admin.equals(this))
				return true;
		}

		return false;
	}

	public static String match(String str) {
		for (AdminCommands admin : AdminCommands.values()) {
			if (admin.toString().startsWith(str))
				return admin.toString();
		}

		return null;
	}


	
	@Override
	public String toString() {
		return super.toString();
	}
}
