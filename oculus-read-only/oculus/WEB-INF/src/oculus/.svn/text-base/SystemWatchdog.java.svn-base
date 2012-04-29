package oculus;

import java.util.Date;
import java.util.Timer;
import java.util.TimerTask;

import oculus.Settings;
import oculus.State;
import oculus.Util;

public class SystemWatchdog {
	
	private final Settings settings;
	private final boolean reboot;
	
	// check every hour
	public static final long DELAY = State.TWO_MINUTES;

	// when is the system stale and need reboot
	public static final long STALE = State.ONE_DAY * 2; 
	
	// shared state variables
	private State state = State.getReference();
	private Application app;
	
    /** Constructor */
	public SystemWatchdog(Application a) {
		app = a;
		settings = new Settings();
		reboot = settings.getBoolean(State.reboot);		
		if (reboot){
			Timer timer = new Timer();
			timer.scheduleAtFixedRate(new Task(), State.TEN_MINUTES, DELAY);
		}	
	}
	
	private class Task extends TimerTask {
		public void run() {
		
			// only reboot is idle 
			if ((state.getUpTime() > STALE) && !state.getBoolean(State.userisconnected)){ 
				
				String boot = new Date(state.getLong(State.boottime)).toString();				
				System.out.println("OCULUS: SystemWatchDog, rebooting, last was: " + boot);
				System.out.println("OCULUS: SystemWatchDog, user logged in for: " + state.getLoginSince() + " ms");
				
				// reboot  
				if (Settings.os.equals("windows")) {
					Util.systemCall("shutdown -r -f -t 01");	
				}
				else {
					Util.systemCall("shutdown -r now");
				}
			}
		}
	}
}
