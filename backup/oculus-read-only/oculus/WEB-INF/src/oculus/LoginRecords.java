package oculus;

import java.util.Date;
import java.util.Vector;

public class LoginRecords {

	public static final String PASSENGER = "passenger";
	public static final String DRIVER = "driver";
	public static final int MAX_RECORDS = 50;
	
	public static Vector<Record> list = new Vector<Record>();
	public static State state = State.getReference();
	public static Settings settings = new Settings();
	private static Application app = null; 
	
	public LoginRecords(){ 
//		Util.debug("started", this);
	}
	
	public void setApplication(Application a) {
		app = a;
//		Util.debug("set application, called", this);		
	}
	
	public void beDriver() { 
		
		list.add(new Record(state.get(State.user), DRIVER)); 
		state.set(State.userisconnected, true);
		state.set(State.logintime, System.currentTimeMillis());
		
		if (settings.getBoolean(Settings.loginnotify))
			if(app!=null)
				app.speech.mluv("lawg inn " + state.get(State.user));
				// accessing 'Speech.mluv' directly so doesn't display text in client window on login 

		Util.log("beDriver(): " + this.toString(), this);
		
		if(list.size()>MAX_RECORDS) list.remove(0); // push out oldest 
	}
	
	public void bePassenger() {		
	
		list.add(new Record(state.get(State.user), PASSENGER)); // "xxx.xxx.xxx.xxx"));
		state.set(State.userisconnected, true);
		
		if (settings.getBoolean(Settings.loginnotify))
			if(app!=null)
				app.saySpeech("lawg inn " + state.get(State.user));

		Util.log("bePassenger(): " + this.toString(), this);
		
		if(list.size()>MAX_RECORDS) list.remove(0); // push out oldest 
	}
	
	/** @return true if this user is already connected from this address
	public boolean isConnected(final String user, final String ip){
		for (int i = 0; i < list.size(); i++){
			Record rec = list.get(i);
			if (rec.isActive()){
				if(rec.getAddress().equals(ip))
					if(rec.getUser().equals(user))
						return true;
			}
		}
		
		return false;
	}*/
	
	
	/** is the current user the admin? */
	public boolean isAdmin() {
		String user = state.get(State.user);
		if (user == null) return false;
		if (user.equals("")) return false;
		Settings settings = new Settings();
		String admin = settings.readSetting("user0").toLowerCase();
		return admin.equals(user.toLowerCase());
	}
	
	
	public void signout() {
		
		if(state.getBoolean(State.developer)){
			System.out.println("+_logging out: " + state.get(State.user));
			System.out.println("+_waiting now:" + getPassengers());
			System.out.println(toString());
		}
		
		// try all instances
		// int active = 0;
		for (int i = 0; i < list.size(); i++){
			Record rec = list.get(i);
			if (rec.isActive()){
				if (rec.getUser().equals(state.get(State.user))){
					list.get(i).logout();
				}
			}
		}
		
		// assume this gets reset as new user logs in 
		state.set(State.userisconnected, false);
		state.delete(State.user);
		
		// maintain size limit 
		if(list.size() > MAX_RECORDS) list.remove(0);
		
		if(state.getBoolean(State.developer)){
			System.out.println("OCULUS: -_logging out: " + state.get(State.user));
			System.out.println("OCULUS: _waiting now:" + getPassengers());
			System.out.println(toString());
		}
	
	}

	/** @return the number of users waiting in line */
	public int getPassengers() {
		int passengers = 0;
		for (int i = 0; i < list.size(); i++){
			Record rec = list.get(i);
			if(rec.isActive() && rec.isPassenger())
				passengers++;
		}

		return passengers;
	}

	/** @return the number of users */
	public int getActive() {
		int active = 0;
		for (int i = 0; i < list.size(); i++){
			Record rec = list.get(i);
			if(rec.isActive())
				active++;
		}

		return active;
	}
	
	/** @return a list of user names waiting in line */
	public String[] getPassengerList() {
		String[] passengers = new String[getPassengers()];
		for (int i = 0; i < list.size(); i++){
			Record rec = list.get(i);
			if(rec.isActive() && rec.isPassenger())
				passengers[i] = rec.getUser();
		}

		return passengers;
	}
	
	/** @return a list of user names */
	public String[] getActiveList() {
		String[] passengers = new String[getActive()];
		for (int i = 0; i < list.size(); i++){
			Record rec = list.get(i);
			if(rec.isActive())
				passengers[i] = rec.getUser();
		}

		return passengers;
	}

	public int size() {
		return list.size();
	}

	/** create snap shot of current use to disk 
	public static boolean save(){
		
		if(new File(Settings.loginactivity).exists()) 
			new File(Settings.loginactivity).delete();
		
		FileWriter fw = null;
		try {
			fw = new FileWriter(new File(Settings.loginactivity));
		} catch (IOException e1) {
			return false;
		}
	
		try {
			fw.append(new LoginRecords().toString());
		} catch (IOException e) {
			return false;
		}
		
		try {
			fw.close();
		} catch (IOException e) {
			return false;
		}
		
		return true;
	}*/

	public String toString() {

		if (list.isEmpty()) return null;

		String str = "current users:\r\n";
		for (int i = 0; i < list.size(); i++)
			str += i + " " + list.get(i).toString() + "\r\n";

		return str;
	}

	/**
	 * 
	 */
	private class Record {

		private long timein = System.currentTimeMillis();
		private long timeout = 0;
		private String user = null;
		private String role = null;
//		private String id = "unkown";
//		private String ip = null;

		Record(String usr, String role){ //, String id) {
			this.user = usr;
			this.role = role;
			//this.ip = addr;
			// this.id = id;
			
			if(state.getBoolean(State.developer))
				Util.log("ceated login: " + toString(), this);
		}

		public String getUser() {
			return user;
		}

		/*
		public long inTime() {
			return timein;
		}

		public long outTime() {
			return timeout;
		}*/
		
		
	//	public String getAddress(){
	//		return ip;
	//	}
		
		public boolean isActive(){
			return (timeout==0);
		}
		
		public boolean isPassenger(){
			return (role.equals(PASSENGER));
		}
		
		@Override
		public String toString() {
			String str = user + " " + role.toUpperCase(); // + " id: " + id;
			//if(!isPassenger()) str += " address: " + ip;
			str += " login: " + new Date(timein).toString();
			if(isActive()) str += " is ACTIVE";
			else str += " logout: " + new Date(timeout).toString();
			
			return str;
		}

		public void logout() {
			if(timeout==0){
				timeout = System.currentTimeMillis();
				Util.log("logged out : " + toString(), this);
			} else Util.log("error: trying to logout twice", this);	
		}
	}
}
