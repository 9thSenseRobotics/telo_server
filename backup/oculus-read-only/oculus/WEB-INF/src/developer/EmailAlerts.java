package developer;

import oculus.Application;
import oculus.Observer;
import oculus.Settings;
import oculus.State;
import oculus.Util;

/** */
public class EmailAlerts implements Observer {

	// how low of battery to warm user with email
	// any lower and my dell will go into 
	// low power mode, need time to park it   
	public static final int WARN_LEVEL = 40;
	private Application app = null;
	private Settings settings;
	private State state = State.getReference();
	
	/** Constructor */
	public EmailAlerts(Application parent) {
		app = parent;
		settings = new Settings();
		//if (settings.getBoolean(Settings.emailalerts)){
			state.addObserver(this);
			oculus.Util.debug("starting email alerts...", this);
		//}
	}

	@Override
	public void updated(String key) {
		
		if( ! key.equals(State.batterylife)) return;
		
		Util.debug(".. checking battey", this);
		
		if (state.getInteger(State.batterylife) < WARN_LEVEL) {
			
			app.message("battery low, sending email", null, null);
			
			String msg = "The battery " + Integer.toString(state.getInteger(State.batterylife)) 
			+ "% and is draining!"; 
							
			// add the link back to the user screen 
			msg += "\n\nPlease find the dock, log in here: http://" 
				+ State.getReference().get(State.externaladdress) 
				+ ":" + settings.readRed5Setting("http.port") 
				+ "/oculus/";
			
			// send email 
			new SendMail("Oculus Message", msg, app); 
			
			// stop listening 
			// state.removeObserver(this);
			
			// TODO: trigger auto dock
			// app.autodock();
		}
	}
}
