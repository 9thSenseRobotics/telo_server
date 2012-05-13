package oculus;

import java.io.File;
import java.util.Collection;
import java.util.Set;
import java.util.UUID;

import oculus.commport.AbstractArduinoComm;
import oculus.commport.Discovery;
import oculus.commport.LightsComm;

import org.red5.server.adapter.MultiThreadedApplicationAdapter;
import org.red5.server.api.IConnection;
import org.red5.server.api.Red5;
import org.red5.server.api.service.IServiceCapableConnection;
import org.jasypt.util.password.*;
import org.red5.io.amf3.ByteArray;

import developer.UpdateFTP;

/** red5 application */
public class Application extends MultiThreadedApplicationAdapter {

	private static final int STREAM_CONNECT_DELAY = 2000;
	private ConfigurablePasswordEncryptor passwordEncryptor = new ConfigurablePasswordEncryptor();

	private static String salt;
	private IConnection grabber = null;
	private IConnection player = null;
	private AbstractArduinoComm comport = null;
	private LightsComm light = null;
	private BatteryLife battery = null;
	private Settings settings = new Settings();
	private String pendinguserconnected = null;
	private String remember = null;
	private IConnection pendingplayer = null;
	private String httpPort;
	private Docker docker = null;
	private State state = State.getReference();
	private boolean initialstatuscalled = false;
	private boolean pendingplayerisnull = true;
	private boolean playerstream = false;
	private LoginRecords loginRecords = new LoginRecords();

	//dev stuff
	private developer.CommandServer commandServer = null;
	private developer.LogManager moves = new developer.LogManager();
	public developer.OpenNIRead openNIRead = null;
	
	// try to make private
	public boolean muteROVonMove = false;
	public String stream = null;
	public Speech speech = new Speech();
	
	//public String os = Settings.os;  //  "linux" or "windows"

	public Application() {
		super();
		
		///if (System.getProperty("os.name").matches("Linux")) { os = "linux"; }
		//else { os = "windows"; }
		//System.out.println("OCULUS: OS = "+os);
		
		passwordEncryptor.setAlgorithm("SHA-1");
		passwordEncryptor.setPlainDigest(true);
		FrameGrabHTTP.setApp(this);
		RtmpPortRequest.setApp(this);
		AuthGrab.setApp(this);
		//settings = new Settings(this);
		initialize();
	}

	@Override
	public boolean appConnect(IConnection connection, Object[] params) {

		String logininfo[] = ((String) params[0]).split(" ");

		// always accept local grabber
		if ((connection.getRemoteAddress()).equals("127.0.0.1")
				&& logininfo[0].equals(""))
			return true;

		if (logininfo.length == 1) { // test for cookie auth
			String username = logintest("", logininfo[0]);
			if (username != null) {
				pendinguserconnected = username;
				return true;
			}
		}
		if (logininfo.length > 1) { // test for user/pass/remember
			String encryptedPassword = (passwordEncryptor
					.encryptPassword(logininfo[0] + salt + logininfo[1])).trim();
			if (logintest(logininfo[0], encryptedPassword) != null) {
				if (logininfo[2].equals("remember")) {
					remember = encryptedPassword;
				}
				pendinguserconnected = logininfo[0];
				return true;
			}
		}
		String str = "login from: " + connection.getRemoteAddress() + " failed";
		System.out.println("OCULUS: appConnect(): " + str);
		messageGrabber(str, "");
		return false;
	}

	@Override
	public void appDisconnect(IConnection connection) {
		if (connection.equals(player)) {
			String str = state.get(State.user) + " disconnected";
			System.out.println("OCULUS: appDisconnect(): " + str); 

			messageGrabber(str, "connection awaiting&nbsp;connection");
		
			// admin = false;			
			// TODO: issue 24
			loginRecords.signout();
			System.out.println("OCULUS: after appDisconnect(): " + loginRecords);
			
			if(settings.getBoolean(Settings.developer))
				if(state.get(State.user) == null)
					System.out.println("OCULUS: user was logged out correctly...");
			
			player = null;

			if (!state.getBoolean(State.autodocking)) {
				if (stream != null) {
					if (!stream.equals("stop")) {
						publish("stop");
					}
				}

				if (light.isConnected()) { // && light.lightLevel != 0) {
					if (light.spotLightBrightness() > 0)
						light.setSpotLightBrightness(0);
					if (light.floodLightOn())
						light.floodLight("off");
				}

				if (comport != null) {
					comport.stopGoing();
					comport.releaseCameraServo();
				}
			}

			if (playerstream) {
				playerstream = false;
				grabberPlayPlayer(0);
				messageGrabber("playerbroadcast", "0");
			}

		}
		if (connection.equals(grabber)) {
			grabber = null;
			// log.info("grabber disconnected");
			// wait a bit, see if still no grabber, THEN reload
			new Thread(new Runnable() {
				public void run() {
					try {
						Thread.sleep(8000);
						if (grabber == null) {
							grabberInitialize();
						}
					} catch (Exception e) {
						e.printStackTrace();
					}
				}
			}).start();
		}
	}

	public void grabbersignin(String mode) {
		if (mode.equals("init")) {
			stream = null;
		} else {
			stream = "stop";
		}
		grabber = Red5.getConnectionLocal();
		String str = "awaiting&nbsp;connection";
		if (state.get(State.user) != null) {
			str = state.get(State.user) + "&nbsp;connected";
		}
		str += " stream " + stream;
		messageGrabber("connected to subsystem", "connection " + str);
		System.out.println("OCULUS: grabber signed in from " + grabber.getRemoteAddress());
		// System.out.println("grabbbersignin");
		// stream = "stop";
		if (playerstream) {
			grabberPlayPlayer(1);
			messageGrabber("playerbroadcast", "1");
		}

		// eliminate any other grabbers
		Collection<Set<IConnection>> concollection = getConnections();
		for (Set<IConnection> cc : concollection) {
			for (IConnection con : cc) {
				if (con instanceof IServiceCapableConnection && con != grabber && con != player
						&& (con.getRemoteAddress()).equals("127.0.0.1")) { 
					con.close();
				}
			}
		}
		
		// set video, audio quality mode in grabber flash, depending on server/client OS
		String videosoundmode="high"; // windows, default
		if (Settings.os.equals("linux")) {
			videosoundmode="low";
		}
		setGrabberVideoSoundMode(videosoundmode);

		// do it differently if sonar on board
		// if( different firmware ??) docker = new BradzAutoDock();
		// TODO: BRAD
		docker = new AutoDock(this, grabber, comport, light);
		loginRecords.setApplication(this);
	}

	/** */
	public void initialize() {

		salt = settings.readSetting("salt");
		if (salt == null) {
			Util.log("initialize(), has no salt!", this);
			salt = UUID.randomUUID().toString();
			settings.newSetting("salt", salt);
		}

		// must call this here
		settings.writeFile();

		// must be blocking search of all ports, but only once!
		Discovery discovery = new Discovery();
		comport = discovery.getMotors(this); 
		light = discovery.getLights(this);
		
		httpPort = settings.readRed5Setting("http.port");
		muteROVonMove = settings.getBoolean(FactorySettings.muteonrovmove);
		
		if (settings.getBoolean(State.developer)) 
			openNIRead = new developer.OpenNIRead(this);
	
		if (settings.readSetting(OptionalSettings.emailaddress)!=null)
			new developer.EmailAlerts(this);
			
		if (settings.getInteger(OptionalSettings.commandport) > State.ERROR)
			commandServer = new developer.CommandServer(this);
		
		if(UpdateFTP.configured()) new developer.UpdateFTP();

		Util.setSystemVolume(settings.getInteger(Settings.volume), this);
		grabberInitialize();
		battery = BatteryLife.getReference();
		new SystemWatchdog(this);
		Util.debug("initialize done", this);

	}

	/**
	 * battery init steps separated here since battery has to be called after
	 * delay and delay can't be in main app, is in server.js instead
	 * 
	 * @param mode
	 *            is this required, not really used?
	 */
	private void checkForBattery(String mode) {
		if (mode.equals("init")) {
			battery.init(this);
		} else {
			new Thread(new Runnable() {
				public void run() {
					if (battery.batteryPresent())
						messageGrabber("populatevalues battery yes", null);
					else
						messageGrabber("populatevalues battery nil", null);
				}
			}).start();
		}
	}

	private void grabberInitialize() {
		if (settings.getBoolean(Settings.skipsetup)) {
			grabber_launch();
		} else {
			initialize_launch();
		}
	}

	public void initialize_launch() {
		new Thread(new Runnable() {
			public void run() {
				try {
					// stream = null;
					String address = "127.0.0.1:" + httpPort;
					if (Settings.os.equals("linux")) {
						Runtime.getRuntime().exec("xdg-open http://" + address + "/oculus/initialize.html");
					}
					else { // win
						Runtime.getRuntime().exec("cmd.exe /c start http://" + address + "/oculus/initialize.html");
					}
				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
	}

	public void grabber_launch() {
		new Thread(new Runnable() {
			public void run() {
				try {

					// stream = "stop";
					String address = "127.0.0.1:" + httpPort;
					if (Settings.os.equals("linux")) {
						Runtime.getRuntime().exec("xdg-open http://" + address + "/oculus/server.html");
					}
					else { // win
						Runtime.getRuntime().exec("cmd.exe /c start http://" + address + "/oculus/server.html");
					}

				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
	}

	/** */
	public void playersignin() {		
		// set video, audio quality mode in grabber flash, depending on server/client OS
		String videosoundmode="high"; // windows, default
		if (Settings.os.equals("linux")) {
			videosoundmode="low";
		}

		if (player != null) {
			pendingplayer = Red5.getConnectionLocal();
			pendingplayerisnull = false;

			if (pendingplayer instanceof IServiceCapableConnection) {
				IServiceCapableConnection sc = (IServiceCapableConnection) pendingplayer;
				String str = "connection PENDING user " + pendinguserconnected;
				if (remember != null) {
					// System.out.println("sending store cookie");
					str += " storecookie " + remember;
					remember = null;
				}
				str += " someonealreadydriving " + state.get(State.user);

				// this has to be last to above variables are already set in
				// java script
				sc.invoke("message", new Object[] { null, "green", "multiple", str });
				str = pendinguserconnected + " pending connection from: "
						+ pendingplayer.getRemoteAddress();
				
				// log.info(str);
				System.out.println("OCULUS: playersignin(): " + str);
				messageGrabber(str, null);
				
				sc.invoke("videoSoundMode", new Object[] { videosoundmode });
			}
		} else {
			player = Red5.getConnectionLocal();
			state.set(State.user, pendinguserconnected);
			String str = "connection connected user " + state.get(State.user);
			if (remember != null) {
				str += " storecookie " + remember;
				remember = null;
			}
			str += " streamsettings " + streamSettings();
			messageplayer(state.get(State.user) + " connected to OCULUS", "multiple", str);
			initialstatuscalled = false;
			
			str = state.get(State.user) + " connected from: " + player.getRemoteAddress();
			messageGrabber(str, "connection " + state.get(State.user) + "&nbsp;connected");
			System.out.println("OCULUS: playersignin(), " + str);
			loginRecords.beDriver();
			
			IServiceCapableConnection sc = (IServiceCapableConnection) player;
			sc.invoke("videoSoundMode", new Object[] { videosoundmode });
			Util.log("player video sound mode = "+videosoundmode, this);
		}
	}


	/**
	 * distribute commands from player
	 * 
	 * @param fn
	 *            is the function to call
	 * 
	 * @param str
	 *            is the parameter to pass onto the function
	 */
	public void playerCallServer(final String fn, final String str) {
		if (fn == null) return;
		if (fn.equals("")) return;
		
		PlayerCommands cmd = null;
		try {
			cmd = PlayerCommands.valueOf(fn);
		} catch (Exception e) {
			Util.debug("playerCallServer() command not found:" + fn, this);
			return;
		}
		if (cmd != null) {
			if (cmd.requiresAdmin())
				if (loginRecords.isAdmin()){ 
					// message("must be an admin", null, null);
					Util.debug("playerCallServer(), must be an admin to do: " + fn, this);
					return;
				}
			playerCallServer(cmd, str);
		}
	}

	public void dockGrab() {
		if (grabber instanceof IServiceCapableConnection) {
			IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
			sc.invoke("dockgrab", new Object[] { 0, 0, "find" });
			state.set(oculus.State.dockgrabbusy, true);
		}
	}

	/**
	 * distribute commands from player
	 * 
	 * @param fn
	 *            to call in flash player [file name].swf
	 * @param str
	 *            is the argument string to pass along
	 */
	public void playerCallServer(final PlayerCommands fn, final String str) {

		final String[] cmd = str.split(" ");

		if (state.getBoolean(State.developer))
			if (!fn.equals(PlayerCommands.statuscheck))
				System.out.println("playerCallServer(): " + fn + " " + str);

		switch (fn) {
		case chat:
			chat(str);
			return;
		case beapassenger:
			beAPassenger(str);
			return;
		case assumecontrol:
			assumeControl(str);
			return;
		}

		 // must be driver/non-passenger for all commands below (or cmdMgr user)
		
		// temp 'solution' 
		if( ! state.getBoolean(oculus.State.override)){
		 if (Red5.getConnectionLocal() != player && player != null) {
			 System.out.println("passenger, command dropped: " + fn.toString());
			 return;
		 }
		}
		
		
		switch (fn) {
		/*
		 * case tail: int lines = 30; if(cmd.length==2) lines =
		 * Integer.parseInt(cmd[1]); String t = Util.tail(new
		 * File(oculus.Settings.stdout), lines);
		 * 
		 * if(t!=null) if(t.length() > 1) out.println(t);
		 * 
		 * break;
		 */

		case writesetting:
			System.out.println("setting: " + str);
			if (settings.readSetting(cmd[0]) == null) {
				settings.newSetting(cmd[0], cmd[1]);
				messageplayer("new setting: " + cmd[1], null, null);
			} else {
				settings.writeSettings(cmd[0], cmd[1]);
				messageplayer(cmd[0] + " " + cmd[1], null, null);
			}
			settings.writeFile();
			break;

		case speedset:
			comport.speedset(str);
			messageplayer("speed set: " + str, "speed", str.toUpperCase());
			break;

		case slide:
			if (!state.getBoolean(State.motionenabled)) {
				messageplayer("motion disabled", "motion", "disabled");
				break;
			}
			if (state.getBoolean(State.autodocking)) {
				messageplayer("command dropped, autodocking", null, null);
				break;
			}
			moveMacroCancel();
			comport.slide(str);
			if (moves != null)
				moves.append("slide " + str);
			messageplayer("command received: " + fn + str, null, null);
			break;

		case systemcall:
			System.out.println("received: " + str);
			messageplayer("system command received", null, null);
			Util.systemCall(str);
			break;

		case relaunchgrabber:
			grabber_launch();
			messageplayer("relaunching grabber", null, null);
			break;

		case docklineposupdate:
			settings.writeSettings("vidctroffset", str);
			messageplayer("vidctroffset set to : " + str, null, null);
			break;

		case arduinoecho:
			if (str.equalsIgnoreCase("on"))
				comport.setEcho(true);
			else
				comport.setEcho(false);
			messageplayer("echo set to: " + str, null, null);
			break;

		case arduinoreset:
			comport.reset();
			messageplayer("resetting arduinoculus", null, null);
			break;

		case move:
			move(str);
			break;
		case nudge:
			nudge(str);
			break;
		case speech:
			saySpeech(str);
			break;
		case dock:
			docker.dock(str);
			break;
		case battstats:
			battery.battStats();
			break;
		case cameracommand:
			cameraCommand(str);
			break;
		case gettiltsettings:
			getTiltSettings();
			break;
		case getdrivingsettings:
			getDrivingSettings();
			break;
		case motionenabletoggle:
			motionEnableToggle();
			break;
		case drivingsettingsupdate:
			drivingSettingsUpdate(str);
			break;
		case tiltsettingsupdate:
			tiltSettingsUpdate(str);
			break;
		case tilttest:
			tiltTest(str);
			break;
		case clicksteer:
			clickSteer(str);
			break;
		case streamsettingscustom:
			streamSettingsCustom(str);
			break;
		case streamsettingsset:
			streamSettingsSet(str);
			break;
		case statuscheck:
			statusCheck(str);
			break;
		case playerexit:
			appDisconnect(player);
			break;
		case playerbroadcast:
			playerBroadCast(str);
			break;
		case password_update:
			account("password_update", str);
			break;
		case new_user_add:
			account("new_user_add", str);
			break;
		case user_list:
			account("user_list", "");
			break;
		case delete_user:
			account("delete_user", str);
			break;
		case framegrab:
			frameGrab();
			break;
		/*case emailgrab:
			frameGrab();
			emailgrab = true;
			break;*/ 
		//TODO: disabled currently 
		case extrauser_password_update:
			account("extrauser_password_update", str);
			break;
		case username_update:
			account("username_update", str);
			break;
		case disconnectotherconnections:
			disconnectOtherConnections();
			break;
		case monitor:
			if (Settings.os.equals("linux")) {
				messageplayer("unsupported in linux",null,null);
			}
			monitor(str);
			break;
		case showlog:
			showlog();
			break;
		case dockgrab:
			dockGrab();
			break;
		case publish:
			publish(str);
			break;
		case autodock:
			docker.autoDock(str);
			break;
		case autodockcalibrate:
			docker.autoDock("calibrate " + str);
			break;
		case restart:
			restart();
			break;
		case softwareupdate:
			softwareUpdate(str);
			break;
		case setsystemvolume:
			Util.setSystemVolume(Integer.parseInt(str), this);
			if (Settings.os.equals("linux")) { messageplayer("unsupported in linux",null,null); }
			else { messageplayer("ROV volume set to "+str+"%", null, null); }
			break;
		case muterovmiconmovetoggle:
			muteROVMicOnMoveToggle();
			break;
		case spotlightsetbrightness:
			light.setSpotLightBrightness(Integer.parseInt(str));
			break;
		case floodlight:
			light.floodLight(str);
			break;
		case holdservo:
			if (str.equalsIgnoreCase("true")) {
				comport.holdservo = true;
				settings.writeSettings(FactorySettings.holdservo.toString(),
						str);
			} else {
				comport.holdservo = false;
				settings.writeSettings(FactorySettings.holdservo.toString(),
						str);
			}
			messageplayer("holdservo " + str, null, null);
			break;
		case opennisensor:
			if(str.equals("on")) { openNIRead.startDepthCam(); }
			else { openNIRead.stopDepthCam(); }			
			messageplayer("openNI camera "+str, null, null);
			break;
		case videosoundmode:
			setGrabberVideoSoundMode(str);
			messageplayer("video/sound mode set to: "+str, null, null);
			break;
		}
	}

	/** put all commands here */
	public enum grabberCommands {
		streammode, saveandlaunch, populatesettings, systemcall, chat, dockgrabbed, autodock, 
		restart, checkforbattery, factoryreset;
		@Override
		public String toString() {
			return super.toString();
		}
	}

	/**
	 * turn string input to command id
	 * 
	 * @param fn
	 *            is the funct ion to call
	 * @param str
	 *            is the parameters to pass on to the function.
	 */
	public void grabberCallServer(String fn, String str) {
		grabberCommands cmd = null;
		try {
			cmd = grabberCommands.valueOf(fn);
		} catch (Exception e) {
			return;
		}

		if (cmd == null)
			return;
		grabberCallServer(cmd, str);
	}

	/**
	 * distribute commands from grabber
	 * 
	 * @param fn
	 *            is the function to call in xxxxxx.swf ???
	 * @param str
	 *            is the parameters to pass on to the function.
	 */
	public void grabberCallServer(final grabberCommands cmd, final String str) {
		switch (cmd) {
		case streammode:
			grabberSetStream(str);
			break;
		case saveandlaunch:
			saveAndLaunch(str);
			break;
		case populatesettings:
			populateSettings();
			break;
		case systemcall:
			Util.systemCall(str);
			break;
		case chat:
			chat(str);
			break;
		case dockgrabbed: 
			docker.autoDock("dockgrabbed " + str);
			String[] arg = str.split(" ");
			state.set(State.dockxpos, arg[1]);
			state.set(State.dockypos, arg[2]);
			state.set(State.dockxsize, arg[3]);
			state.set(State.dockysize, arg[4]);
			state.set(State.dockslope, arg[5]);
			state.set(State.dockgrabbusy, false);
			break;
		case autodock: 
			docker.autoDock(str);
			break;
		case checkforbattery:
			checkForBattery(str);
			break;
		case factoryreset:
			factoryReset();
			break;
		case restart:
			restart();
			break;
		}
	}

	private void grabberSetStream(String str) {
		stream = str;

		if (str.equals("camera") || str.equals("camandmic")) {
			if (comport != null && comport.holdservo) {
				comport.camToPos(comport.camservopos);
			}
		}

		if (str.equals("stop") || str.equals("mic")) {
			if (comport != null && comport.holdservo)
				comport.releaseCameraServo();
			state.delete(PlayerCommands.publish);
		}

		// messageplayer("streaming "+str,"stream",stream);
		messageGrabber("streaming " + stream, "stream " + stream);
		System.out.println("OCULUS: streaming " + stream);
		new Thread(new Runnable() {
			public void run() {
				try {
					Thread.sleep(STREAM_CONNECT_DELAY);
					Collection<Set<IConnection>> concollection = getConnections();
					for (Set<IConnection> cc : concollection) {
						for (IConnection con : cc) {
							if (con instanceof IServiceCapableConnection
									&& con != grabber
									&& !(con == pendingplayer && !pendingplayerisnull)) {
								IServiceCapableConnection n = (IServiceCapableConnection) con;
								n.invoke("message", new Object[] {
										"streaming " + stream, "green",
										"stream", stream });
							}
						}
					}
				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
	}

	private void setGrabberVideoSoundMode(String str) {
		if (state.getBoolean(State.autodocking)) {
			messageplayer("command dropped, autodocking", null, null);
			return;
		}

		if (stream == null) {
			messageplayer("stream control unavailable, server may be in setup mode", null, null);
			return;
		}
		
		IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
		sc.invoke("videoSoundMode", new Object[] { str });
		Util.log("grabber video sound mode = "+str, this);
	}
	
	public void publish(String str) {
		if (state.getBoolean(State.autodocking)) {
			messageplayer("command dropped, autodocking", null, null);
			return;
		}

		if (stream == null) {
			messageplayer("stream control unavailable, server may be in setup mode", null, null);
			return;
		}

		try {
			// commands: camandmic camera mic stop
			if (grabber instanceof IServiceCapableConnection) {
				IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
				String current = settings.readSetting("vset");
				String vals[] = (settings.readSetting(current)).split("_");
				int width = Integer.parseInt(vals[0]);
				int height = Integer.parseInt(vals[1]);
				int fps = Integer.parseInt(vals[2]);
				int quality = Integer.parseInt(vals[3]);
				sc.invoke("publish", new Object[] { str, width, height, fps, quality });
				// messageGrabber("stream "+str);
				messageplayer("command received: publish " + str, null, null);
				state.set(PlayerCommands.publish, str);
				System.out.println("PUBLISHED:" + state.get(PlayerCommands.publish));
			}
		} catch (NumberFormatException e) {
			System.out.println("publish() " + e.getMessage());
			e.printStackTrace();
		}
	}

	public void muteROVMic() {
		if (grabber == null) return;
		if (stream == null) return;
		if (grabber instanceof IServiceCapableConnection
				&& (stream.equals("camandmic") || stream.equals("mic"))) {
			IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
			sc.invoke("muteROVMic", new Object[] {});
		}
	}

	public void unmuteROVMic() {
		if (grabber == null) return;
		if (stream == null) return;
		if (grabber instanceof IServiceCapableConnection
				&& (stream.equals("camandmic") || stream.equals("mic"))) {
			IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
			sc.invoke("unmuteROVMic", new Object[] {});
		}
	}

	private void muteROVMicOnMoveToggle() {
		if (muteROVonMove) {
			muteROVonMove = false;
			settings.writeSettings("mute_rov_on_move", "no");
			messageplayer("mute ROV onmove off", null, null);
		} else {
			muteROVonMove = true;
			settings.writeSettings("mute_rov_on_move", "yes");
			messageplayer("mute ROV onmove on", null, null);
		}
	}

	/**  */
	public boolean frameGrab() {

		 if(state.getBoolean(State.framegrabbusy) || !(stream.equals("camera") || stream.equals("camandmic"))) {
			 System.out.println("OCULUS: framegrab busy or unavailable, command dropped");
			 return false;
		 }

		if (grabber instanceof IServiceCapableConnection) {
			IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
			sc.invoke("framegrab", new Object[] {});
			// messageplayer("framegrab command received", null, null);
			state.set(State.framegrabbusy, true);
		}
		return true;
	}

	/** */
	public void frameGrabbed(ByteArray _RAWBitmapImage) { // , final String
															// filename) {
		/*
		// Use functionality in org.red5.io.amf3.ByteArray to get parameters of
		// the ByteArray
		int BCurrentlyAvailable = _RAWBitmapImage.bytesAvailable();
		int BWholeSize = _RAWBitmapImage.length(); // Put the Red5 ByteArray
													// into a standard Java
													// array of bytes
		byte c[] = new byte[BWholeSize];
		_RAWBitmapImage.readBytes(c);

		// Transform the byte array into a java buffered image
		ByteArrayInputStream db = new ByteArrayInputStream(c);

		if (BCurrentlyAvailable > 0) {
			// System.out.println("The byte Array currently has "
			// + BCurrentlyAvailable + " bytes. The Buffer has " +
			// db.available());
			try {
				BufferedImage JavaImage = ImageIO.read(db);
				// Now lets try and write the buffered image out to a file
				if (JavaImage != null) {
					// If you sent a jpeg to the server, just change PNG to JPEG
					// and Red5ScreenShot.png to .jpeg
					ImageIO.write(JavaImage, "JPEG", new File(Settings.framefile));
					if (emailgrab) {
						emailgrab = false;
						new developer.SendMail("Oculus Screen Shot",
								"image attached", Settings.framefile, this);
					}
				}
			} catch (IOException e) {
				//log.info("Save_ScreenShot: Writing of screenshot failed " + e);
				System.out.println("OCULUS: IO Error " + e);
			}
		}
		*/
		int BCurrentlyAvailable = _RAWBitmapImage.bytesAvailable();
		int BWholeSize = _RAWBitmapImage.length(); // Put the Red5 ByteArray
													// into a standard Java
													// array of bytes
		byte c[] = new byte[BWholeSize];
		_RAWBitmapImage.readBytes(c);
		if (BCurrentlyAvailable > 0) {
			state.set(State.framegrabbusy, false);
			// state.set("framegrablast", false);
			
			FrameGrabHTTP.img = c;
			AuthGrab.img = c;

			// TODO: BRAD !!
		}
	}

	private void messageplayer(String str, String status, String value) {
		if (player instanceof IServiceCapableConnection) {
			IServiceCapableConnection sc = (IServiceCapableConnection) player;
			sc.invoke("message", new Object[] { str, "green", status, value });
		}
		// System.out.println(str);
	}

	private void sendplayerfunction(String fn, String params) {
		if (player instanceof IServiceCapableConnection) {
			IServiceCapableConnection sc = (IServiceCapableConnection) player;
			sc.invoke("playerfunction", new Object[] { fn, params });
		}
	}

	public void saySpeech(String str) {
		if (Settings.os.equals("linux")) {
			messageplayer("unsupported in linux",null,null);
			return;
		}
		messageplayer("synth voice: " + str, null, null);
		messageGrabber("synth voice: " + str, null);
		//Speech speech = new Speech();   // DONT initialize each time here, takes too long
		speech.mluv(str);
		// Util.systemCall("nircmdc.exe speak text \""+str+"\"", true);
		// log.info("voice synth: '" + str + "'");
	}

	private void getDrivingSettings() {
		if (loginRecords.isAdmin()) {
			String str = comport.speedslow + " " + comport.speedmed + " "
					+ comport.nudgedelay + " " + comport.maxclicknudgedelay
					+ " " + comport.clicknudgemomentummult + " "
					+ comport.steeringcomp;
			sendplayerfunction("drivingsettingsdisplay", str);
		}
	}

	private void drivingSettingsUpdate(String str) {
		if (loginRecords.isAdmin()) {
			String comps[] = str.split(" ");
			comport.speedslow = Integer.parseInt(comps[0]);
			settings.writeSettings("speedslow",
					Integer.toString(comport.speedslow));
			comport.speedmed = Integer.parseInt(comps[1]);
			settings.writeSettings("speedmed",
					Integer.toString(comport.speedmed));
			comport.nudgedelay = Integer.parseInt(comps[2]);
			settings.writeSettings("nudgedelay",
					Integer.toString(comport.nudgedelay));
			comport.maxclicknudgedelay = Integer.parseInt(comps[3]);
			settings.writeSettings("maxclicknudgedelay",
					Integer.toString(comport.maxclicknudgedelay));
			comport.clicknudgemomentummult = Double.parseDouble(comps[4]);
			settings.writeSettings("clicknudgemomentummult",
					Double.toString(comport.clicknudgemomentummult));
			int n = Integer.parseInt(comps[5]);
			if (n > 255) {
				n = 255;
			}
			if (n < 0) {
				n = 0;
			}
			comport.steeringcomp = n;
			settings.writeSettings("steeringcomp",
					Integer.toString(comport.steeringcomp));
			comport.updateSteeringComp();
			String s = comport.speedslow + " " + comport.speedmed + " "
					+ comport.nudgedelay + " " + comport.maxclicknudgedelay
					+ " " + comport.clicknudgemomentummult + " "
					+ (comport.steeringcomp - 128);
			messageplayer("driving settings set to: " + s, null, null);
		}
	}

	public void message(String str, String status, String value) {
		messageplayer(str, status, value);
	}

	private void getTiltSettings() {
		if (loginRecords.isAdmin()) {
			String str = comport.camservohoriz + " " + comport.camposmax + " "
					+ comport.camposmin + " " + comport.maxclickcam + " "
					+ settings.readSetting("videoscale");
			sendplayerfunction("tiltsettingsdisplay", str);
		}
	}

	private void tiltSettingsUpdate(String str) {
		if (loginRecords.isAdmin()) {
			String comps[] = str.split(" ");
			comport.camservohoriz = Integer.parseInt(comps[0]);
			settings.writeSettings("camservohoriz",
					Integer.toString(comport.camservohoriz));
			comport.camposmax = Integer.parseInt(comps[1]);
			settings.writeSettings("camposmax",
					Integer.toString(comport.camposmax));
			comport.camposmin = Integer.parseInt(comps[2]);
			settings.writeSettings("camposmin",
					Integer.toString(comport.camposmin));
			comport.maxclickcam = Integer.parseInt(comps[3]);
			settings.writeSettings("maxclickcam",
					Integer.toString(comport.maxclickcam));
			settings.writeSettings("videoscale", comps[4]);
			String s = comport.camservohoriz + " " + comport.camposmax + " "
					+ comport.camposmin + " " + comport.maxclickcam + " " + comps[4];
			messageplayer("cam settings set to: " + s, "videoscale", comps[4]);
		}
	}

	private void tiltTest(String str) {
		comport.camToPos(Integer.parseInt(str));
		messageplayer("cam position: " + str, null, null);
	}

	private void moveMacroCancel() {
		if (state.getBoolean(State.docking)) {
			String str = "";
			if (!state.equals(State.dockstatus, State.docked)) {
				state.set(State.dockstatus, State.undocked);
				str += "dock un-docked";
			}
			messageplayer("docking cancelled by movement", "multiple", str);
			state.set(State.docking, false);
		}
		if (comport.sliding == true)
			comport.slidecancel();
	}

	private void cameraCommand(String str) {

		if (state.getBoolean(State.autodocking)) {
			messageplayer("command dropped, autodocking", null, null);
			return;
		}

		comport.camCommand(str);
		messageplayer("tilt command received: " + str, null, null);
		if (!str.equals("up") && !str.equals("down") && !str.equals("horiz")) {
			messageplayer(null, "cameratilt", camTiltPos());
		}
		if (str.equals("horiz")) {
			messageplayer(null, "cameratilt", "0&deg;");
		}
	}

	public String camTiltPos() {
		int n = comport.camservohoriz - comport.camservopos;
		n *= -1;
		String s = "";
		if (n > 0) {
			s = "+";
		}
		return s + Integer.toString(n) + "&deg;";
	}

	private void statusCheck(String s) {
		if (initialstatuscalled == false) {
			initialstatuscalled = true;
			battery.battStats();

			// build string
			String str = "";
			if (comport != null) {
				String spd = "FAST";
				if (comport.speed == comport.speedmed)
					spd = "MED";
				if (comport.speed == comport.speedslow)
					spd = "SLOW";

				String mov = "STOPPED";
				if (!state.getBoolean(State.motionenabled))
					mov = "DISABLED";
				if (comport.moving == true)
					mov = "MOVING";
				str += " speed " + spd + " cameratilt " + camTiltPos()
						+ " motion " + mov;
			}

			str += " vidctroffset " + settings.readSetting("vidctroffset");
			str += " rovvolume " + settings.readSetting(Settings.volume);
			str += " stream " + stream + " selfstream stop";
			if (loginRecords.isAdmin())
				str += " admin true";
			if (state.get(State.dockstatus) != null)
				str += " dock " + state.get(State.dockstatus);
			if (light.isConnected()) {
				str += " light " + light.spotLightBrightness();
			}
			if (settings.getBoolean(State.developer) == true) {
				str += " developer true";
			}

			String videoScale = settings.readSetting("videoscale");
			if (videoScale != null) {
				str += " videoscale " + videoScale;
			}

			messageplayer("status check received", "multiple", str.trim());

		} else {
			messageplayer("status check received", null, null);
		}

		if (s.equals("battcheck"))
			battery.battStats();
	}

	private void streamSettingsCustom(String str) {
		settings.writeSettings("vset", "vcustom");
		settings.writeSettings("vcustom", str);
		String s = "custom stream set to: " + str;
		if (!stream.equals("stop") && !state.getBoolean(State.autodocking)) {
			publish(stream);
			s += "<br>restarting stream";
		}
		messageplayer(s, null, null);
		System.out.println("stream changed to " + str);
	}

	private void streamSettingsSet(String str) {
		settings.writeSettings("vset", "v" + str);
		String s = "stream set to: " + str;
		if (!stream.equals("stop") && !state.getBoolean(State.autodocking)) {
			publish(stream);
			s += "<br>restarting stream";
		}
		messageplayer(s, null, null);
		System.out.println("OCULUS: stream changed to " + str);
	}

	private String streamSettings() {
		String result = "";
		result += settings.readSetting("vset") + "_";
		result += settings.readSetting("vlow") + "_"
				+ settings.readSetting("vmed") + "_";
		result += settings.readSetting("vhigh") + "_"
				+ settings.readSetting("vfull") + "_";
		result += settings.readSetting("vcustom");
		return result;
	}

	public void restart() {
		if (Settings.os.equals("linux")) { 
			messageplayer("unsupported in linux",null,null);
			messageGrabber("unsupported in linux", null);
			return;
		}

		messageplayer("restarting server application", null, null);
		messageGrabber("restarting server application", null);
		File f;
		f = new File(System.getenv("RED5_HOME") + "\\restart");
		try {
			if (!f.exists()) {
				f.createNewFile();
			}
			Runtime.getRuntime().exec("red5-shutdown.bat");
		} catch (Exception e) {
			e.printStackTrace();
		}
		// }
	}

	public void monitor(String str) {
		// uses nircmd.exe from http://www.nirsoft.net/utils/nircmd.html
		if (Settings.os.equals("linux")) {
			// messageplayer("unsupported in linux",null,null);
			return;
		}
		messageplayer("monitor " + str, null, null);
		str = str.trim();
		if (str.equals("on")) {
			str = "cmd.exe /c start monitoron.bat";
		} else {
			str = "nircmdc.exe monitor async_off";
		}
		try {
			Runtime.getRuntime().exec(str);
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	/**
	 * Move the bot in given direction if not busy docking
	 * 
	 * @param str
	 *            parameter is the direction
	 */
	public void move(String str) {

		if (str == null)
			return;

		if (str.equals("stop")) {
			if (state.getBoolean(State.autodocking))
				docker.autoDock("cancel");

			comport.stopGoing();
			moveMacroCancel();
			message("command received: " + str, "motion", "STOPPED");
			if (moves != null)
				moves.append("move " + str);
			return;
		}

		moveMacroCancel();

		// Issue#4 - use autodock cancel if needed
		if (state.getBoolean(State.autodocking)) {
			messageplayer("command dropped, autodocking", null, null);
			return;
		}

		if (!state.getBoolean(State.motionenabled)) {
			messageplayer("motion disabled (try un-dock)", "motion", "DISABLED");
			return;
		}

		if (str.equals("forward"))
			comport.goForward();
		else if (str.equals("backward"))
			comport.goBackward();
		else if (str.equals("right"))
			comport.turnRight();
		else if (str.equals("left"))
			comport.turnLeft();

		messageplayer("command received: " + str, "motion", "MOVING");
		if (moves != null) moves.append("move " + str);

	}

	/**
	 * 
	 * @param str
	 *            is the direction to move.
	 * 
	 *            Valid choices are: "right", "left", "backward", "forward"
	 * 
	 */
	public void nudge(String str) {

		if (str == null) return;
		if (!state.getBoolean(State.motionenabled)) {
			messageplayer("motion disabled", "motion", "disabled");
			return;
		}

		if (state.getBoolean(State.autodocking)) {
			messageplayer("command dropped, autodocking", null, null);
			return;
		}

		comport.nudge(str);
		messageplayer("command received: nudge" + str, null, null);
		moves.append("nudge " + str);
		if (state.getBoolean(State.docking)
				|| state.getBoolean(State.autodocking))
			moveMacroCancel();
	}

	private void motionEnableToggle() {
		if (state.getBoolean(State.motionenabled)) {
			state.set(State.motionenabled, "false");
			messageplayer("motion disabled", "motion", "disabled");
		} else {
			state.set(State.motionenabled, "true");
			messageplayer("motion enabled", "motion", "enabled");
		}
	}

	/**
	 * 
	 * @param str
	 */
	private void clickSteer(String str) {

		if (str == null)
			return;
		if (!state.getBoolean(State.motionenabled)) {
			messageplayer("motion disabled", "motion", "disabled");
			return;
		}

		if (state.getBoolean(State.autodocking)) {
			messageplayer("command dropped, autodocking", null, null);
			return;
		}

		if (moves != null)
			moves.append("clicksteer " + str);

		int n = comport.clickSteer(str);
		if (n != 999) {
			messageplayer("received: clicksteer " + str, "cameratilt",
					camTiltPos());
		} else {
			messageplayer("received: clicksteer " + str, null, null);
		}

		moveMacroCancel();

	}

	/** */
	public void messageGrabber(String str, String status) {
		// System.out.println(str);
		if (grabber instanceof IServiceCapableConnection) {
			IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
			sc.invoke("message", new Object[] { str, status });
		}
	}

	public String logintest(String user, String pass) {
		int i;
		String value = "";
		String returnvalue = null;
		if (user.equals("")) {
			i = 0;
			while (true) {
				value = settings.readSetting("pass" + i);
				if (value == null) {
					break;
				} else {
					if (value.equals(pass)) {
						returnvalue = settings.readSetting("user" + i);
						break;
					}
				}
				i++;
			}
		} else {
			i = 0;
			while (true) {
				value = settings.readSetting("user" + i);
				// System.out.println(value);
				if (value == null) {
					break;
				} else {
					if (value.equals(user)) {
						if ((settings.readSetting("pass" + i)).equals(pass)) {
							returnvalue = user;
						}
						break;
					}
				}
				i++;
			}
		}
		return returnvalue;
	}

	/** */
	private void assumeControl(String user) {
		messageplayer("controls hijacked", "hijacked", user);
		IConnection tmp = player;
		player = pendingplayer;
		pendingplayer = tmp;
		state.set(State.user, user);
		String str = "connection connected streamsettings " + streamSettings();
		messageplayer(state.get(State.user) + " connected to OCULUS", "multiple", str);
		str = state.get(State.user) + " connected from: " + player.getRemoteAddress();
		System.out.println("OCULUS: assumeControl(), " + str);
		messageGrabber(str, null);
		initialstatuscalled = false;
		pendingplayerisnull = true;
		loginRecords.beDriver();
	}

	/** */
	private void beAPassenger(String user) {
		pendingplayerisnull = true;
		String str = user + " added as passenger";
		messageplayer(str, null, null);
		// log.info(str);
		System.out.println("OCULUS: " + str);
		messageGrabber(str, null);
		if (!stream.equals("stop")) {
			Collection<Set<IConnection>> concollection = getConnections();
			for (Set<IConnection> cc : concollection) {
				for (IConnection con : cc) {
					if (con instanceof IServiceCapableConnection
							&& con != grabber && con != player) {
						IServiceCapableConnection sc = (IServiceCapableConnection) con;
						sc.invoke("message", new Object[] {
								"streaming " + stream, "green", "stream", stream });
					}
				}
			}
		}
		loginRecords.bePassenger();
	}

	private void playerBroadCast(String str) {
		if (player instanceof IServiceCapableConnection) {
			IServiceCapableConnection sc = (IServiceCapableConnection) player;
			if (!str.equals("off")) {
				String vals[] = (settings.readSetting("vself")).split("_");
				int width = Integer.parseInt(vals[0]);
				int height = Integer.parseInt(vals[1]);
				int fps = Integer.parseInt(vals[2]);
				int quality = Integer.parseInt(vals[3]);
				sc.invoke("publish", new Object[] { str, width, height, fps,
						quality });
				// sc.invoke("publish", new Object[] { str, 160, 120, 8, 85 });
				new Thread(new Runnable() {
					public void run() {
						try {
							Thread.sleep(STREAM_CONNECT_DELAY);
						} catch (Exception e) {
							e.printStackTrace();
						}
						grabberPlayPlayer(1);
						playerstream = true;
					}
				}).start();
				System.out.println("OCULUS: player broadcast start");
			} else {
				sc.invoke("publish", new Object[] { "stop", null, null, null,null });
				grabberPlayPlayer(0);
				playerstream = false;
				System.out.println("OCULUS: player broadcast stop");
			}
		}
	}

	private void grabberPlayPlayer(int nostreams) {
		if (grabber instanceof IServiceCapableConnection) {
			IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
			sc.invoke("play", new Object[] { nostreams });
			// messageGrabber("playerbroadcast",Integer.toString(nostreams));
		}
	}

	private void account(String fn, String str) {
		if (fn.equals("password_update")) {
			passwordChange(state.get(State.user), str);
		}
		if (loginRecords.isAdmin()){ // admin) {
			if (fn.equals("new_user_add")) {
				String message = "";
				Boolean oktoadd = true;
				String u[] = str.split(" ");
				if (!u[0].matches("\\w+")) {
					message += "error: username must be letters/numbers only ";
					oktoadd = false;
				}
				if (!u[1].matches("\\w+")) {
					message += "error: password must be letters/numbers only ";
					oktoadd = false;
				}
				int i = 0;
				String s;
				while (true) {
					s = settings.readSetting("user" + i);
					if (s == null) {
						break;
					}
					if ((s.toUpperCase()).equals((u[0]).toUpperCase())) {
						message += "ERROR: user name already exists ";
						oktoadd = false;
					}
					i++;
				}
				// add check for existing user, user loop below to get i while
				// you're at it
				if (oktoadd) {
					message += "added user " + u[0];
					settings.newSetting("user" + i, u[0]);
					String p = u[0] + salt + u[1];
					String encryptedPassword = passwordEncryptor
							.encryptPassword(p);
					settings.newSetting("pass" + i, encryptedPassword);
				}
				messageplayer(message, null, null);
			}
			if (fn.equals("user_list")) {
				int i = 1;
				String users = "";
				String u;
				while (true) {
					u = settings.readSetting("user" + i);
					if (u == null) {
						break;
					} else {
						users += u + " ";
					}
					i++;
				}
				sendplayerfunction("userlistpopulate", users);
			}
			if (fn.equals("delete_user")) {
				int i = 1;
				int usernum = -1;
				int maxusernum = -1;
				String[] allusers = new String[999];
				String[] allpasswords = new String[999];
				String u;
				while (true) { // read & store all users+passwords, note number
								// to be deleted, and max number
					u = settings.readSetting("user" + i);
					if (u == null) {
						maxusernum = i - 1;
						break;
					}
					if (u.equals(str)) {
						usernum = i;
					}
					allusers[i] = u;
					allpasswords[i] = settings.readSetting("pass" + i);
					i++;
				}
				if (usernum > 0) {
					i = usernum;
					while (i <= maxusernum) { // delete user to be delted + all
												// after
						settings.deleteSetting("user" + i);
						settings.deleteSetting("pass" + i);
						i++;
					}
					i = usernum + 1;
					while (i <= maxusernum) { // shuffle remaining past deleted
												// one, down one
						settings.newSetting("user" + (i - 1), allusers[i]);
						settings.newSetting("pass" + (i - 1), allpasswords[i]);
						i++;
					}
				}
				messageplayer(str + " deleted.", null, null);
			}
			if (fn.equals("extrauser_password_update")) {
				String s[] = str.split(" ");
				passwordChange(s[0], s[1]);
			}
			if (fn.equals("username_update")) {
				String u[] = str.split(" ");
				String message = "";
				Boolean oktoadd = true;
				if (!u[0].matches("\\w+")) {
					message += "error: username must be letters/numbers only ";
					oktoadd = false;
				}
				int i = 1;
				String s;
				while (true) {
					s = settings.readSetting("user" + i);
					if (s == null) {
						break;
					}
					if ((s.toUpperCase()).equals(u[0].toUpperCase())) {
						message += "error: user name already exists ";
						oktoadd = false;
					}
					i++;
				}
				String encryptedPassword = (passwordEncryptor
						.encryptPassword(state.get(State.user) + salt + u[1]))
						.trim();
				if (logintest(state.get(State.user), encryptedPassword) == null) {
					message += "error: wrong password";
					oktoadd = false;
				}
				if (oktoadd) {
					message += "username changed to: " + u[0];
					messageplayer("username changed to: " + u[0], "user", u[0]);
					settings.writeSettings("user0", u[0]);
					state.set(State.user, u[0]);
					String p = u[0] + salt + u[1];
					encryptedPassword = passwordEncryptor.encryptPassword(p);
					settings.writeSettings("pass0", encryptedPassword);
				} else {
					messageplayer(message, null, null);
				}
			}
		}
	}

	private void passwordChange(String user, String pass) {
		String message = "password updated";
		// pass = pass.replaceAll("\\s+$", "");
		if (pass.matches("\\w+")) {
			String p = user + salt + pass;
			String encryptedPassword = passwordEncryptor.encryptPassword(p);
			int i = 0;
			String u;
			while (true) {
				u = settings.readSetting("user" + i);
				if (u == null) {
					break;
				} else {
					if (u.equals(user)) {
						settings.writeSettings("pass" + i, encryptedPassword);
						break;
					}
				}
				i++;
			}
		} else {
			message = "error: password must be alpha-numeric with no spaces";
		}
		messageplayer(message, null, null);
	}

	private void disconnectOtherConnections() {
		if (loginRecords.isAdmin()) {
			int i = 0;
			Collection<Set<IConnection>> concollection = getConnections();
			for (Set<IConnection> cc : concollection) {
				for (IConnection con : cc) {
					if (con instanceof IServiceCapableConnection
							&& con != grabber && con != player) {
						con.close();
						i++;
					}
				}
			}
			messageplayer(i + " passengers eliminated", null, null);
		}
	}

	private void chat(String str) {
		Collection<Set<IConnection>> concollection = getConnections();
		for (Set<IConnection> cc : concollection) {
			for (IConnection con : cc) {
				if (con instanceof IServiceCapableConnection && con != grabber
						&& !(con == pendingplayer && !pendingplayerisnull)) {
					IServiceCapableConnection n = (IServiceCapableConnection) con;
					n.invoke("message", new Object[] { str, "yellow", null, null });
				}
			}
		}
		System.out.println("OCULUS: chat: " + str);
		messageGrabber("<CHAT>" + str, null);
		if (commandServer != null) commandServer.sendToGroup(str);
	}

	private void showlog() {
	
		//System.out.println
		// messageplayer("", "debug", "OCULUS: showlog");
		
		String[] tail = Util.tail(new File(Settings.stdout), "OCULUS");
		String str = null;
		if(tail!=null){
			
			// debug ... 
			str = "&bull; returned: " + tail.length + "lines from: " + 
				Settings.stdout + "<br>";
			
			for(int i = 0 ; i < tail.length ; i++)
				str += "&bull; " + tail[i].substring(
						"OCULUS:".length(), tail[i].length()) + "<br>";
			
			sendplayerfunction("showserverlog", str);

		}
		
		
		//if (loginRecords.isAdmin()) {
						
		//	FileInputStream filein;
		//	String str = "";
		//	try {
		//		filein = new FileInputStream(oculus.Settings.stdout);
		//		BufferedReader reader = new BufferedReader(
		//				new InputStreamReader(filein));
		//		String line = "";
		//		while ((line = reader.readLine()) != null) {
		//			str = "&bull; " + line + "<br>" + str;
		//		}
		
		//		filein.close();
		//		sendplayerfunction("showserverlog", str);
		//	} catch (Exception e) {
		//		e.printStackTrace();
		//	}
			
		// }
	}

	private void saveAndLaunch(String str) {
		System.out.println("saveandlaunch: " + str);
		String message = "";
		Boolean oktoadd = true;
		Boolean restartrequired = false;
		String user = null;
		String password = null;
		String httpport = null;
		String rtmpport = null;
		String skipsetup = null;

		String s[] = str.split(" ");
		for (int n = 0; n < s.length; n = n + 2) {
			// user password comport httpport rtmpport skipsetup developer
			if (s[n].equals("user")) {
				user = s[n + 1];
			}
			if (s[n].equals("password")) {
				password = s[n + 1];
			}
			if (s[n].equals("httpport")) {
				httpport = s[n + 1];
			}
			if (s[n].equals("rtmpport")) {
				rtmpport = s[n + 1];
			}
			if (s[n].equals("skipsetup")) {
				skipsetup = s[n + 1];
			}
		}

		// user & password
		if (user != null) {
			if (!user.matches("\\w+")) {
				message += "Error: username must be letters/numbers only ";
				oktoadd = false;
			}
			if (!password.matches("\\w+")) {
				message += "Error: password must be letters/numbers only ";
				oktoadd = false;
			}
			int i = 1; // admin user = 0, start from 1 (non admin)
			String name;
			while (true) {
				name = settings.readSetting("user" + i);
				if (name == null) {
					break;
				}
				if ((name.toUpperCase()).equals((user).toUpperCase())) {
					message += "Error: non-admin user name already exists ";
					oktoadd = false;
				}
				i++;
			}
			if (oktoadd) {
				String p = user + salt + password;
				String encryptedPassword = passwordEncryptor.encryptPassword(p);
				if (settings.readSetting("user0") == null) {
					settings.newSetting("user0", user);
					settings.newSetting("pass0", encryptedPassword);
				} else {
					settings.writeSettings("user0", user);
					settings.writeSettings("pass0", encryptedPassword);
				}
			}
		} else {
			if (settings.readSetting("user0") == null) {
				oktoadd = false;
				message += "Error: admin user not defined ";
			}
		}

		// httpport
		if (httpport != null) {
			if (!(settings.readRed5Setting("http.port")).equals(httpport)) {
				restartrequired = true;
			}
			settings.writeRed5Setting("http.port", httpport);
		}
		// rtmpport
		if (rtmpport != null) {
			if (!(settings.readRed5Setting("rtmp.port")).equals(rtmpport)) {
				restartrequired = true;
			}
			settings.writeRed5Setting("rtmp.port", rtmpport);
		}

		if (oktoadd) {
			// skipsetup
			if (skipsetup != null) {
				settings.writeSettings("skipsetup", skipsetup);
			}

			message = "launch server";
			if (restartrequired) {
				message = "shutdown";
				// admin = true;
				restart();
			}
		}
		messageGrabber(message, null);
	}

	/** */
	private void populateSettings() {
		settings.writeSettings("skipsetup", "no");
		String result = "populatevalues ";

		// username
		String str = settings.readSetting("user0");
		if (str != null)
			result += "username " + str + " ";

		// comport
		if (state.get(State.serialport) == null)
			result += "comport nil ";
		else
			result += "comport " + state.get(State.serialport) + " ";

		// lights
		if (state.get(State.lightport) == null)
			result += "lightport nil ";
		else
			result += "lightport " + state.get(State.lightport) + " ";

		// law and wan
		String lan = state.get(State.localaddress);
		;
		if (lan == null)
			result += "lanaddress error ";
		else
			result += "lanaddress " + lan + " ";

		String wan = state.get(State.externaladdress);
		;
		if (wan == null)
			result += "wanaddress error ";
		else
			result += "wanaddress " + wan + " ";

		// http port
		result += "httpport " + settings.readRed5Setting("http.port") + " ";

		// rtmp port
		result += "rtmpport " + settings.readRed5Setting("rtmp.port") + " ";

		settings.writeFile();
		messageGrabber(result, null);
	}

	public void softwareUpdate(String str) {
		if (Settings.os.equals("linux")) {
			messageplayer("unsupported in linux",null,null);
			return;
		}

		if (str.equals("check")) {
			messageplayer("checking for new software...", null, null);
			Updater updater = new Updater();
			int currver = updater.getCurrentVersion();
			String fileurl = updater.checkForUpdateFile();
			int newver = updater.versionNum(fileurl);
			if (newver > currver) {
				String message = "New version available: v." + newver + "\n";
				if (currver == -1) {
					message += "Current software version unknown\n";
				} else {
					message += "Current software is v." + currver + "\n";
				}
				message += "Do you want to download and install?";
				messageplayer("new version available", "softwareupdate",
						message);
			} else {
				messageplayer("no new version available", null, null);
			}
		}
		if (str.equals("download")) {
			messageplayer("downloading software update...", null, null);
			new Thread(new Runnable() {
				public void run() {
					Updater up = new Updater();
					final String fileurl = up.checkForUpdateFile();
					System.out.println("downloading url: " + fileurl);
					Downloader dl = new Downloader();
					if (dl.FileDownload(fileurl, "update.zip", "download")) {
						messageplayer("update download complete, unzipping...",
								null, null);

						// this is a blocking call
						if (dl.unzipFolder("download\\update.zip", "webapps"))
							messageplayer("done.", "softwareupdate",
									"downloadcomplete");

						// not needed now is unpacked
						dl.deleteFile("download\\update.zip");

						// backup config
						settings.writeFile("oculus_settings_backup_"
								+ up.getCurrentVersion() + ".txt");

					} else {
						messageplayer("update download failed", null, null);
					}
				}
			}).start();
		}
		if (str.equals("versiononly")) {
			int currver = new Updater().getCurrentVersion();
			String msg = "";
			if (currver == -1)
				msg = "version unknown";
			else
				msg = "version: v." + currver;
			messageplayer(msg, null, null);
		}
	}

	public void factoryReset() {

		final String backup = "oculus_factory_reset.txt";

		// backup
		new File(Settings.settingsfile).renameTo(new File(backup));

		// delete it, build on startup
		new File(Settings.settingsfile).delete();

		restart();
	}
}