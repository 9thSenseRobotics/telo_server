package oculus;

import java.util.Enumeration;
import java.util.Properties;
import java.util.Vector;

public class State {

	public static final String SEPERATOR = " : ";

	public static final String user = "user";
	public static final String logintime = "logintime";
	public static final String userisconnected = "userisconnected";
	public static final String reboot = "reboot";
	public static final String developer = "developer";
	public static final String serialport = "serialport";
	public static final String lightport = "lightport";
	public static final String target = "target";
	public static final String boottime = "boottime";
	public static final String batterylife = "batterylife";
	public static final String batterystatus = "batterstatus";
	public static final String motionenabled = "motionenabled";
	public static final String externaladdress = "externaladdress";
	public static final String localaddress = "localaddress";
	public static final String autodocktimeout = "autodocktimeout";
	public static final String autodocking = "autodocking";
	public static final String timeout = "timeout";
	public static final String losttarget = "losttarget";
	public static final String firmware = "firmware";
	public static final String unknown = "unknown";	
	public static final String override = "override";

	public static final String commwatchdog = "commwatchdog";
	public static final String framegrabbusy = "framegrabbusy";
	
	public static final String sonarback = "sonarback";
	public static final String sonarright = "sonarright";
	public static final String sonarleft = "sonarleft";
	
	public static final String dockgrabbusy = "dockgrabbusy";
	public static final String docking = "docking";
	public static final String dockxsize = "dockxsize";	
	public static final String dockysize = "dockysize";
	public static final String dockstatus = "dockstatus";
	public static final String dockgrabtime = "dockgrabtime";
	public static final String dockslope = "dockslope";
	public static final String dockxpos = "dockxpos";
	public static final String dockypos = "dockypos";
	public static final String docked = "docked";
	public static final String undocked = "undocked";
	public static final String undock = "undock";
		
	public static final long ONE_DAY = 86400000;
	public static final long ONE_MINUTE = 60000;
	public static final long TWO_MINUTES = 60000;
	public static final long FIVE_MINUTES = 300000;
	public static final long TEN_MINUTES = 600000;
	public static final int ERROR = -1;



	/** notify these on change events */
	public Vector<Observer> observers = new Vector<Observer>();
	
	/** reference to this singleton class */
	private static State singleton = null;

	/** properties object to hold configuration */
	private Properties props = new Properties();
	
	public static State getReference() {
		if (singleton == null) {
			singleton = new State();
		}
		return singleton;
	}

	/** private constructor for this singleton class */
	private State() {
		props.put(boottime, String.valueOf(System.currentTimeMillis()));
		props.put(localaddress, Util.getLocalAddress());
		new Thread(new Runnable() {
			@Override
			public void run() {
				String ip = null; 
				while(ip==null){
					ip = Util.getExternalIPAddress();
					if(ip!=null)
						State.getReference().set(State.externaladdress, ip);
					else Util.delay(500);
				}
			}
		}).start();
	}
	
	/** */
	public Properties getProperties(){
		return (Properties) props.clone();
	}

	/** */
	public void addObserver(Observer obs){
		observers.add(obs);
	}
	
	/** test for string equality. any nulls will return false */ 
	public boolean equals(final String a, final String b){
		String aa = get(a);
		if(aa==null) return false; 
		if(b==null) return false; 
		if(aa.equals("")) return false;
		if(b.equals("")) return false;
		
		return aa.equalsIgnoreCase(b);
	}
	
	/** debug */
	public void dump(){
		System.out.println("state number of listeners: " + observers.size());
		for(int i = 0 ; i < observers.size() ; i++) 
			System.out.println(i + " " + observers.get(i).getClass().getName() + "\n");
		
		Enumeration<Object> keys = props.keys();
		while(keys.hasMoreElements()){
			String key = (String) keys.nextElement();
			String value = (String) props.getProperty(key);			
			System.out.println(key + SEPERATOR + value);
		}
	}
	
	/** */
	@Override
	public String toString(){	
		String str = "";// new String("state listeners: " + observers.size());
		Enumeration<Object> keys = props.keys();
		while(keys.hasMoreElements()){
			String key = (String) keys.nextElement();
			String value = (String) props.getProperty(key);					
			str += key + SEPERATOR + value + "\r\n";
		}	
		return str;
	}
	
	/**/
	public boolean block(final String member, final String target, int timeout){
		
		long start = System.currentTimeMillis();
		String current = null;
		while(true){
			
			// keep checking 
			current = get(member); 
			
			if(current!=null){
				if(target.equals("*")) return true;	
				if(target.equals(current)) return true;
				if(target.startsWith(current)) return true;
			}
				
			//
			// TODO: FIX ?? 
			//
			Util.delay(50);
			//System.out.print(".");
			if (System.currentTimeMillis()-start > timeout){ 
				//System.out.println();
				return false;
			}
		}
	}
	
	
	/** Put a name/value pair into the configuration */
	public synchronized void set(final String key, final String value) {
		try {
			props.put(key.trim(), value.trim());
		} catch (Exception e) {
			e.printStackTrace();
		}
		
		for(int i = 0 ; i < observers.size() ; i++)
			observers.get(i).updated(key.trim());	
	}

	/** Put a name/value pair into the config */
	public void set(final String key, final long value) {
		set(key, Long.toString(value));
	}
	
	/** */
	public synchronized String get(final String key) {

		String ans = null;
		try {

			ans = props.getProperty(key.trim());

		} catch (Exception e) {
			System.err.println(e.getStackTrace());
			return null;
		}

		return ans;
	}
	
	/** */
	public synchronized boolean getBoolean(String key) {
		key = key.toLowerCase();
		boolean value = false;
		try {

			value = Boolean.parseBoolean(get(key));

		} catch (Exception e) {
			if(key.equals("yes")) return true;
			else return false;
		}

		return value;
	}

	/** */
	public int getInteger(final String key) {

		String ans = null;
		int value = ERROR;

		try {

			ans = get(key);
			value = Integer.parseInt(ans);

		} catch (Exception e) {
			return ERROR;
		}

		return value;
	}
	
	/** */
	public long getLong(final String key) {

		String ans = null;
		long value = ERROR;

		try {

			ans = get(key);
			value = Long.parseLong(ans);

		} catch (Exception e) {
			return ERROR;
		}

		return value;
	}
	
	/** @return the ms since last boot */
	public long getUpTime(){
		return System.currentTimeMillis() - getLong(boottime);
	}
	
	/** @return the ms since last user log in */
	public long getLoginSince(){
		return System.currentTimeMillis() - getLong(logintime);
	}

	/** */
	public synchronized void set(String key, boolean b) {
		if(b) set(key, "true");
		else set(key, "false");
	}
	
	/** */
	public synchronized boolean exists(String key) {
		return props.contains(key);
	}

	/** */ 
	public synchronized void delete(String key) {
		props.remove(key);
		for(int i = 0 ; i < observers.size() ; i++)
			observers.get(i).updated(key);	
	}

	public void delete(PlayerCommands cmd) {
		delete(cmd.toString());
	}

	public void set(PlayerCommands cmd, String str) {
		set(cmd.toString(), str);
	}
	
	public String get(PlayerCommands cmd){ 
		return get(cmd.toString()); 
	}
	
}