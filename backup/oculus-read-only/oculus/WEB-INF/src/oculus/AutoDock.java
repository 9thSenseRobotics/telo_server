package oculus;

import oculus.commport.AbstractArduinoComm;
import oculus.commport.LightsComm;

import org.red5.server.api.IConnection;
import org.red5.server.api.service.IServiceCapableConnection;

import developer.LogManager;

public class AutoDock implements Docker {

	/* notes 
	 * 
	 * 	boolean autodocking = false;
	 *	String docktarget; // calibration values
	 *     s[] = 0 lastBlobRatio,1 lastTopRatio,2 lastMidRatio,3 lastBottomRatio,4 x,5 y,6 width,7 height,8 slope
	 *     UP CLOSE 85x70  1.2143_0.23563_0.16605_0.22992_124_126_85_70_0.00000
	 *     FAR AWAY 18x16   1.125_0.22917_0.19792_0.28819_144_124_18_16_0.00000

	 *  
	 * 
	 * 1st go click: dockgrab_findfromxy
	 *  MODE1 if autodocking = true:
	 * 		if size <= S1, 
	 * 	 		if not centered: clicksteer to center, dockgrab_find [BREAK]
	 *     		else go forward CONST time, dockgrab_find [BREAK]
		 * 		if size > S1 && size <=S2	 
	 * 			determine N based on slope and blobsize magnitude
	 *  		if not centered +- N: clicksteer to center +/- N, dockgrab_find [BREAK]
	 *   		go forward N time
	 * 		if size > S2 
	 *   		if slope and XY not within target:
	 *   			backup, dockgrab_find
	 *   		else :
	 *      		dock
	 *  END MODE1 
	 * 
	 * events: 
	 *   dockgrabbed_find => enter MODE1
	 *   dockgrabbed_findfromxy => enter MODE1
	 * 
	 */

	// private static Logger log = Red5LoggerFactory.getLogger(AutoDock.class, "oculus");

	private State state = State.getReference();
	private Settings settings;
	private BatteryLife life = BatteryLife.getReference();
	private LogManager moves = null; 
	private IConnection grabber = null;
	private String docktarget = null;
	private AbstractArduinoComm comport = null;
	private LightsComm light = null;
	private Application app = null;
	
	private boolean autodockingcamctr = false;
	private int autodockctrattempts = 0;
	
	public AutoDock(Application theapp, IConnection thegrab, AbstractArduinoComm com, LightsComm light){
		this.app = theapp;
		this.grabber = thegrab;
		this.comport = com;
		this.light = light;
		settings = new Settings();
		if(settings.getBoolean(Settings.developer)){
			moves = new LogManager();
			moves.open(Settings.movesfile);
		}
	}
	

	@Override
	public void autoDock(String str) {
		
		if(moves != null) moves.append("autodock " + str);
		
		String cmd[] = str.split(" ");
		if (cmd[0].equals("cancel")) {
			state.set(State.autodocking, false);
			app.message("auto-dock ended","multiple","cameratilt " +app.camTiltPos()+" autodockcancelled blank motion stopped");
			System.out.println("OCULUS: autodock cancelled");
		}
		if (cmd[0].equals("go")) {
			if (state.getBoolean(State.motionenabled)) { 
				if(state.getBoolean(State.autodocking)){
					app.message("auto-dock in progress", null, null);
					return;
				}
				
				IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
				
				if (light.isConnected()) {
					if (light.spotLightBrightness() == 0 && !light.floodLightOn())
						{ app.monitor("on"); }
					if (light.spotLightBrightness() > 0) {
						light.setSpotLightBrightness(0);
						light.floodLight("on");
					}
				}				
				else { app.monitor("on"); }

				sc.invoke("dockgrab", new Object[] {0,0,"start"}); // sends xy, but they're unuseds
				state.set(State.autodocking, true);
				autodockingcamctr = false;
				//autodockgrabattempts = 0;
				autodockctrattempts = 0;
				app.message("auto-dock in progress", "motion", "moving");
				System.out.println("OCULUS: autodock started");
				
			}
			else { app.message("motion disabled","autodockcancelled", null); }
		}
		if (cmd[0].equals("dockgrabbed")) { // RESULTS FROM GRABBER: calibrate, findfromxy, find
			if ((cmd[1].equals("find") || cmd[1].equals("findfromxy")) && state.getBoolean(State.autodocking)) { // x,y,width,height,slope
				String s = cmd[2]+" "+cmd[3]+" "+cmd[4]+" "+cmd[5]+" "+cmd[6];
			
				if (cmd[4].equals("0")) { // width==0, failed to find target
				
					state.set(State.autodocking, false);	
					state.set(State.docking, false);	
					state.set(State.losttarget, true);	
					app.message("auto-dock target not found, try again","multiple", 
							/*"cameratilt "+app.camTiltPos()+ */" autodockcancelled blank");
					System.out.println("OCULUS: target lost");

				}
				else {
					//autodockgrabattempts++;
					app.message(null,"autodocklock",s);
					autoDockNav(Integer.parseInt(cmd[2]),Integer.parseInt(cmd[3]),Integer.parseInt(cmd[4]),
						Integer.parseInt(cmd[5]),new Float(cmd[6]));
				}
			}
			if (cmd[1].equals("calibrate")) { 
				// x,y,width,height,slope,lastBlobRatio,lastTopRatio,lastMidRatio,lastBottomRatio
				docktarget = cmd[7]+"_"+cmd[8]+"_"+cmd[9]+"_"+cmd[10]+"_"+cmd[2]+"_"+cmd[3]+"_"+cmd[4]+"_"+cmd[5]+"_"+cmd[6];
				settings.writeSettings("docktarget", docktarget); 
				String s = cmd[2]+" "+cmd[3]+" "+cmd[4]+" "+cmd[5]+" "+cmd[6];
				//messageplayer("dock"+cmd[1]+": "+s,"autodocklock",s);
				app.message("auto-dock calibrated","autodocklock",s);
			}
		}
		if (cmd[0].equals("calibrate")) {
			int x = Integer.parseInt(cmd[1])/2; //assuming 320x240
			int y = Integer.parseInt(cmd[2])/2; //assuming 320x240
			if (grabber instanceof IServiceCapableConnection) {
				IServiceCapableConnection sc = (IServiceCapableConnection) grabber;
				sc.invoke("dockgrab", new Object[] {x,y,"calibrate"});
			}
		}
		if (cmd[0].equals("getdocktarget")) {
			docktarget = settings.readSetting("docktarget");
			app.messageGrabber("docksettings", docktarget);
			// System.out.println("OCULUS: got dock target: " + docktarget);
		}
	}
	
	/** */
	public void dock(String str) {
		if (str.equals("dock") && !state.getBoolean(State.docking)) {
			if (state.getBoolean(State.motionenabled)){
				if (!life.batteryCharging()) {
					
					// moves.append("dock " + str);
					app.message("docking initiated", "multiple", "speed fast motion moving dock docking");

					// need to set this because speedset calls goForward also if true
					comport.movingforward = false; 
					comport.speedset("fast"); 
					state.set(State.docking, true);
					state.set(State.dockstatus, State.docking);
					new Thread(new Runnable() {
						public void run() {
							int counter = 0;
							int n;
							while(state.getBoolean(State.docking)) {
								
								n = 200; // when speed=fast
								if (counter <= 3) n += 200;  // when speed=fast
								if (counter > 0) app.message(null,"motion","moving"); 
								comport.goForward();
								Util.delay(n);
								comport.stopGoing();
								app.message(null,"motion","stopped");
								if (life.batteryStatus() == 2) {
									state.set(State.docking, false);
									String str = "";
									if (state.getBoolean(State.autodocking)) {
										state.set(State.autodocking, "false");
										str += " cameratilt "+app.camTiltPos()+" autodockcancelled blank";
										if (!app.stream.equals("stop") && state.get(State.user)==null) { 
											app.publish("stop"); 
										}
										
										if (light.isConnected()) {
											if (light.floodLightOn()) {
												light.floodLight("off");
											}
											else { app.monitor("off"); }
										}									
										else { app.monitor("off"); }
										
									}
									app.message("docked successfully", "multiple", "motion disabled dock docked battery charging"+str);
									System.out.println("OCULUS: " + state.get(State.user) +" docked successfully");
									state.set(State.motionenabled, false);
									state.set(State.dockstatus, State.docked);
									// needs to be before battStats()
									if (settings.getBoolean(State.developer)){
										moves.append("docked successfully");
									}
									life.battStats(); 
									

									
									break;
								}
								counter += 1;
								if (counter >12) { // failed
									
									state.set(State.docking, false);
									state.set(State.timeout, true);

									String s = "dock un-docked";
									if (comport.moving) { 
										comport.stopGoing();
										s += " motion stopped";
									} 
									app.message("docking timed out", "multiple", s);
									System.out.println("OCULUS: " + state.get(State.user) +" docking timed out");
									state.set(State.dockstatus, State.undocked);
									if (state.getBoolean(State.autodocking)) {
										new Thread(new Runnable() { public void run() { try {
											comport.speedset("fast");
											comport.goBackward();
											Thread.sleep(2000);
											comport.stopGoing();
											app.dockGrab();
										} catch (Exception e) { e.printStackTrace(); } } }).start();
									}
									break;
								}
							}
						}
					}).start();
				}
				else { app.message("**battery indicating charging, auto-dock unavailable**", null, null); }
			}
			else { app.message("motion disabled", null, null); }
		}
		if (str.equals(State.undock)) {
			if(state.getBoolean(State.autodocking)){
				app.message("command dropped, autodocking", null, null);
				return;
			}
			
			state.set(State.motionenabled, true);
			comport.speedset("fast");
			comport.goBackward();
			app.message("un-docking", "multiple", "speed fast motion moving dock un-docked");
			state.set(State.dockstatus, State.undocked);
			new Thread(new Runnable() {
				public void run() {
					Util.delay(2000);
					comport.stopGoing();
					app.message("disengaged from dock", "motion", "stopped");
					System.out.println("OCULUS: " + state.get(State.user) + " un-docked");
					life.battStats();
				}
			}).start();
		}
	}

	/** */ 
	private void autoDockNav(int x, int y, int w, int h, float slope) {
		
		x =x+(w/2); //convert to center from upper left
		y=y+(h/2);  //convert to center from upper left
		String s[] = docktarget.split("_");
		// s[] = 0 lastBlobRatio,1 lastTopRatio,2 lastMidRatio,3 lastBottomRatio,4 x,5 y,6 width,7 height,8 slope
		// 0.71053_0.27940_0.16028_0.31579_123_93_81_114_0.014493
		// neg slope = approaching from left
		int rescomp = 2;
		int dockw = Integer.parseInt(s[6]);
		int dockh = Integer.parseInt(s[7]);
		int dockx = Integer.parseInt(s[4]) + dockw/2;
		float dockslope = new Float(s[8]);
		float slopedeg = (float) ((180 / Math.PI) * Math.atan(slope));
		float dockslopedeg = (float) ((180 / Math.PI) * Math.atan(dockslope));
		int s1 = dockw*dockh * 20/100 *  w/h; // was 15/100 w/ taller marker
		int s2 = (int) (dockw*dockh * 65.5/100 * w/h);   // was 92/100 w/ taller marker
		// System.out.println(dockslopedeg+" "+slopedeg);
		
		// optionally set breaking delay longer for fast bots
		int bd = settings.getInteger(OptionalSettings.stopdelay.toString());
		if(bd==Settings.ERROR) bd = 500;
		final int stopdelay = bd;
		
		if (w*h < s1) { 
			if (Math.abs(x-160) > 10 || Math.abs(y-120) > 25) { // clicksteer and go (y was >50)
				comport.clickSteer((x-160)*rescomp+" "+(y-120)*rescomp);
				new Thread(new Runnable() { 

				public void run() { try {
					Thread.sleep(1500); // was 1500 w/ dockgrab following
					comport.speedset("fast");
					comport.goForward();
					Thread.sleep(1500);
					comport.stopGoing();
					Thread.sleep(stopdelay);
					app.dockGrab();

				} catch (Exception e) { e.printStackTrace(); } } }).start();
			}
			else { // go only 
				new Thread(new Runnable() { public void run() { try {
					comport.speedset("fast");
					comport.goForward();
					Thread.sleep(1500);
					comport.stopGoing();
					Thread.sleep(stopdelay); // let deaccelerate
					app.dockGrab();
				} catch (Exception e) { e.printStackTrace(); } } }).start();
			}
		} // end of S1 check
		if (w*h >= s1 && w*h < s2) {
			if (autodockingcamctr) { // if cam centered do check and comps below
				autodockingcamctr = false;
				int autodockcompdir = 0;
				if (Math.abs(slopedeg-dockslopedeg) > 1.7) {
					autodockcompdir = (int) (160 -(w*1.0) -20 -Math.abs(160 -x));  // was 160 - w - 25 - Math.abs(160-x)
				}
				if (slope > dockslope) { autodockcompdir *= -1; } // approaching from left
				autodockcompdir += x + (dockx - 160);
				//System.out.println("comp: "+autodockcompdir);
				if (Math.abs(autodockcompdir-dockx) > 10 || Math.abs(y-120) > 30) { // steer and go 
					comport.clickSteer((autodockcompdir-dockx)*rescomp+" "+(y-120)*rescomp); 
					new Thread(new Runnable() { public void run() { try {
						Thread.sleep(1500); 
						comport.speedset("fast");
						comport.goForward();
						Thread.sleep(450);
						comport.stopGoing();
						Thread.sleep(stopdelay); // let deaccelerate
						app.dockGrab();
					} catch (Exception e) { e.printStackTrace(); } } }).start();
				}
				else { // go only 
					new Thread(new Runnable() { public void run() { try {
						comport.speedset("fast");
						comport.goForward();
						Thread.sleep(500);
						comport.stopGoing();
						Thread.sleep(stopdelay); // let deaccelerate
						app.dockGrab();
					} catch (Exception e) { e.printStackTrace(); } } }).start();
				}
			}
			else { // !autodockingcamctr
				autodockingcamctr = true;
				if (Math.abs(x-dockx) > 10 || Math.abs(y-120) > 15) { // (y was >30)
					comport.clickSteer((x-dockx)*rescomp+" "+(y-120)*rescomp);
					new Thread(new Runnable() { public void run() { try {
						Thread.sleep(1500);
						app.dockGrab();
					} catch (Exception e) { e.printStackTrace(); } } }).start();
				}
				else {
					app.dockGrab();
				}
			}
		}
		if (w*h >= s2) {
			if ((Math.abs(x-dockx) > 5) && autodockctrattempts <= 10) {
				autodockctrattempts ++;
				comport.clickSteer((x-dockx)*rescomp+" "+(y-120)*rescomp);
				new Thread(new Runnable() { public void run() { try {
					Thread.sleep(1500);
					app.dockGrab();
				} catch (Exception e) { e.printStackTrace(); } } }).start();
			}
			else {
				if (Math.abs(slopedeg-dockslopedeg) > 1.6 || autodockctrattempts >10) { // backup and try again
//					System.out.println("backup "+dockslopedeg+" "+slopedeg+" ctrattempts:"+autodockctrattempts);
					autodockctrattempts = 0; 
					int comp = 80;
					if (slope < dockslope) { comp = -80; }
					x += comp;
					comport.clickSteer((x-dockx)*rescomp+" "+(y-120)*rescomp);
					new Thread(new Runnable() { public void run() { try {
						Thread.sleep(1500);
						comport.speedset("fast");
						comport.goBackward();
						Thread.sleep(1500); 
						comport.stopGoing();
						Thread.sleep(stopdelay); // let deaccelerate
						app.dockGrab();
					} catch (Exception e) { e.printStackTrace(); } } }).start();
					System.out.println("OCULUS: autodock backup");
				}
				else { 
//					System.out.println("dock "+dockslopedeg+" "+slopedeg);
					new Thread(new Runnable() { public void run() { try {
						Thread.sleep(100);
						dock("dock"); 
					} catch (Exception e) { e.printStackTrace(); } } }).start();
				}
			}
		}
	}
}

