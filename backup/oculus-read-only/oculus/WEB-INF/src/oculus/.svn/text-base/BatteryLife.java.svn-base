package oculus;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;

import com.jacob.activeX.ActiveXComponent;
import com.jacob.com.Dispatch;
import com.jacob.com.EnumVariant;
import com.jacob.com.Variant;

public class BatteryLife {
	
	/**
	 * Determine how much battery life is left (in percent).
	 * 
	 * 
	 * [CA] windows code originally found here: http://www.dreamincode.net/code/snippet3300.htm
	 */

	private String host;
	private String connectStr;
	private String query; 
	private ActiveXComponent axWMI;
		
	private boolean battcharging = false;
	private boolean batterypresent = false;
	private static Application app = null;
	private static BatteryLife singleton = null;
	private State state = State.getReference();
	private String linuxBattDir = "";

	/**
	 * @return a reference to this singleton class 
	 */
	public static BatteryLife getReference() {
		if (singleton  == null) {
			singleton = new BatteryLife();
		}
		return singleton;
	}

	/**
	 * @param parent this the multi threaded red5 application to call back 
	 */
	public void init(Application parent){
		
		//System.out.println("battery init...");
			
		if(app == null){
			
			// only initialize once 
			app = parent;	
			
			if(Settings.os.equals("windows")){
				host = "localhost"; 
				connectStr = String.format("winmgmts:\\\\%s\\root\\CIMV2", host);
				query = "Select * from Win32_Battery"; 
				axWMI = new ActiveXComponent(connectStr);		
			} 
			else { // linux battery, no init action required like windows, but determine which BATx dir
				try {
					//Util.log("Linux batt init", this);
					Process proc = Runtime.getRuntime().exec("ls /proc/acpi/battery");
					BufferedReader procReader = new BufferedReader(new InputStreamReader(proc.getInputStream()));
					String line = null;
					String str = "";
					while ((line = procReader.readLine()) != null) {
						str += line + " ";
					}
					String dirs[] = str.split(" ");
					if (dirs.length > 0) { 
						int minnum = 999; // most systems will have less than 999 batteries...
						int num = 0;
						for (String dir : dirs) {
							num = Integer.parseInt(dir.toLowerCase().replace("bat", ""));
							if (num < minnum) { minnum = num; }
						}
						linuxBattDir = "/proc/acpi/battery/BAT"+Integer.toString(num);
					}
					Util.log("linux battery found at: "+linuxBattDir, this);
				} 
				catch (IOException e) { e.printStackTrace(); }
			}
		} 
	}
	
	/** 
	 * private constructor, definition of singleton. 	  
	 */
	private BatteryLife() {}
	
	public boolean batteryPresent(){
		if( batteryStatus() == 999 ) batterypresent = false; 
		else batterypresent = true; 
			
		return batterypresent;
	}
	
	public boolean batteryCharging(){
		return battcharging;
	}
	
	/** threaded update, will set values in application via call back */
	public void battStats() { 
		
		if(app == null){
			Util.debug("app not yet configured", this);
			return;
		}
		
		if(batterypresent == false){
			Util.debug("no battery found", this);
			return;
		}
		
		new Thread(new Runnable() {
			public void run() {
			
				if (batterypresent == false) {
					Util.debug("no battery found", this);
					return;
				}
				
				if ( ! state.equals(State.dockstatus, State.docking)){
								
					int batt[] = battStatsCombined();
					String life = Integer.toString(batt[0]);
					int s = batt[1];
					String status = Integer.toString(s); // in case its not 1 or 2
					String str;
					if (s == 1) {
						status = "draining";
						str = "battery " + life + "%," + status;
						state.set(State.batterystatus, status);
						if (!state.getBoolean(State.motionenabled)) {
							state.set(State.motionenabled, true);
							str += " motion enabled";
						}
						if (! state.equals(State.dockstatus, State.undocked)) {
							state.set(State.dockstatus, State.undocked);
							str += " dock un-docked";
						}
						battcharging = false;
						app.message(null, "multiple", str);
					}
					if (s == 2) {
						status = "CHARGING";
						if (life.equals("99") || life.equals("100")) {
							status = "CHARGED";
						}
						battcharging = true;
						str = "battery " + life + "%," + status;
						if (state.get(State.dockstatus) == null) {
							state.set(State.dockstatus, State.docked);
							str += " dock docked";
						}
						app.message(null, "multiple", str);
						state.set(State.batterystatus, "charging");
					}						
				
					state.set(State.batterylife, life);
				}
			}
		}).start();
	}
	
	/** @return the percentage of battery life, or 999 if no battery present */
	public int batteryStatus() {

		if(app == null){
			System.out.println("app not yet configured");
			return 999;
		}
	
		int result = 999;
		
		if (Settings.os.equals("windows")) {	
			//Execute the query
			Variant vCollection = axWMI.invoke("ExecQuery", new Variant(query));
			
			//Our result is a collection, so we need to work though the collection.
			// (it is odd, but there may be more than one battery... think about multiple
			//   UPS on the system).
			EnumVariant enumVariant = new EnumVariant(vCollection.toDispatch());
			Dispatch item = null;
			while (enumVariant.hasMoreElements()) { 
				item = enumVariant.nextElement().toDispatch(); // throws errors sometimes
				int status = Dispatch.call(item,"BatteryStatus").getInt();
				result = status;
			}
		}
		else { // linux
			try {
				Thread.sleep(1000);
			} catch (InterruptedException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
			result = linuxBattStatus();
		}		
//		Util.log(Integer.toString(result), this);		
		return result;
	}
	
	/**
	 * get battery info 
	 * 
	 * @return the charge remaining and status if found, null if not.  
	 */
	private int[] battStatsCombined() {

		if(app == null){
			System.out.println("app not yet configured");
			return null;
		}
		
		if(!batterypresent){
			System.out.println("no battery found");
			return null;
		}
	
		int[] result = { 999, 999 };
		if (Settings.os.equals("windows")) {
			
			Variant vCollection = axWMI.invoke("ExecQuery", new Variant(query));
			EnumVariant enumVariant = new EnumVariant(vCollection.toDispatch());
			Dispatch item = null;
			while (enumVariant.hasMoreElements()) { 
				item = enumVariant.nextElement().toDispatch();
				result[0] = Dispatch.call(item,"EstimatedChargeRemaining").getInt();
				result[1] = Dispatch.call(item,"BatteryStatus").getInt();
			}
		}
		else { // linux
			result[0] = linuxBattPercent();
			result[1] = linuxBattStatus();
		}
		return result;
	}
	
	/**
	 * get linux battery status
	 * @return 1 if draining, 2 if charging
	 */
	private int linuxBattStatus() {
		Process proc;
		int r = 999; // unknown, no battery 
		if (!linuxBattDir.equals("")) {
			try {
				proc = Runtime.getRuntime().exec("cat "+linuxBattDir+"/state");
				BufferedReader procReader = new BufferedReader(new InputStreamReader(proc.getInputStream()));
				String line = null;
				while ((line = procReader.readLine()) != null) {
					String words[] = line.split(":");
					for (String word : words) {
						if (word.toLowerCase().trim().equals("discharging")) {
							r = 1;
							break;
						}
						if (word.toLowerCase().trim().equals("charging") || 
								word.toLowerCase().trim().equals("charged")) {
							r = 2;
							break;
						}
					}				
				}
			} catch (IOException e) {
				e.printStackTrace();
			}
		}
		return r;
	}
	
	/**
	 * get Linux battery charge remaining
	 * @return 0-100 
	 */
	private int linuxBattPercent() {
		Process proc;
		BufferedReader procReader;
		int r = 999; // unknown
		try {
			double capacity = 0.0;
			double remainingCapacity = 0.0;
			//get capacity
			proc = Runtime.getRuntime().exec("cat "+linuxBattDir+"/info");
			procReader = new BufferedReader(new InputStreamReader(proc.getInputStream()));
			String line = null;
			while ((line = procReader.readLine()) != null) {
				String words[] = line.split(":");
				if (words[0].toLowerCase().trim().equals("last full capacity")) {
					String[] c = words[1].trim().split(" ");
					capacity = Double.parseDouble(c[0]);
					break;
				}								
			}
			//get current mWh
			proc = Runtime.getRuntime().exec("cat "+linuxBattDir+"/state");
			procReader = new BufferedReader(new InputStreamReader(proc.getInputStream()));
			line = null;
			while ((line = procReader.readLine()) != null) {
				String words[] = line.split(":");
				if (words[0].toLowerCase().trim().equals("remaining capacity")) {
					String[] c = words[1].trim().split(" ");
					remainingCapacity = Double.parseDouble(c[0]);
					break;
				}								
			}
			r = (int) (remainingCapacity/capacity*100.0);
		} catch (IOException e) {
			e.printStackTrace();
		}
		return r;
	}
}
