package oculus;

import java.io.*;
import java.util.Properties;


public class Settings {

	public static String framefile;
	public static String loginactivity;
	public static String settingsfile;
	public static String movesfile;
	public static String stdout;
	public static String ftpconfig;
	
	// put all constants here
	// public static final String emailalerts = "emailalerts";
	public static final String volume = "volume";
	public static final String loginnotify = "loginnotify";
	public static final String skipsetup = "skipsetup";
	public static final String developer = "developer";
	public static final int ERROR = -1;
	
	public final static String sep = System.getProperty("file.separator");
	public static String os = "windows" ;  //  "linux" or "windows" 
	
	/** create new file if missing */
	public Settings(){
		
		if (System.getProperty("os.name").matches("Linux")) { os = "linux"; }
		
		// framefile = System.getenv("RED5_HOME") + sep+"webapps"+sep+"oculus"+sep+"images"+sep+"framegrab.jpg"; 
		
		ftpconfig = System.getenv("RED5_HOME") +sep+"conf"+sep+"ftp.properties";
		loginactivity = System.getenv("RED5_HOME") +sep+"log"+sep+"loginactivity.txt";
		settingsfile = System.getenv("RED5_HOME") +sep+"conf"+sep+"oculus_settings.txt";
		movesfile = System.getenv("RED5_HOME") +sep+"log"+sep+"moves.txt";
		stdout = System.getenv("RED5_HOME") +sep+"log"+sep+"jvm.stdout";
		
		// be sure of basic configuration 
		if( ! new File(settingsfile).exists()) FactorySettings.createFile();
	}

	/**
	 * lookup values from props file
	 * 
	 * @param key
	 *            is the lookup value
	 * @return the matching value from properties file (or false if not found)
	 */
	public boolean getBoolean(String key) {
		if (key == null)
			return false;
		String str = readSetting(key);
		if (str == null)
			return false;
		if (str.toUpperCase().equals("YES"))
			return true;
		else if (str.toUpperCase().equals("TRUE"))
			return true;
		return false;
	}

	/**
	 * lookup values from props file
	 * 
	 * @param key
	 *            is the lookup value
	 * @return the matching value from properties file (or zero if not found)
	 */
	public int getInteger(String key) {

		String ans = null;
		int value = ERROR;

		try {

			ans = readSetting(key);
			value = Integer.parseInt(ans);

		} catch (Exception e) {
			return ERROR;
		}

		return value;
	}

	/**
	 * lookup values from props file
	 * 
	 * @param key
	 *            is the lookup value
	 * @return the matching value from properties file (or zero if not found)
	 */
	public double getDouble(String key) {

		String ans = null;
		double value = ERROR;

		try {

			ans = readSetting(key);
			value = Double.parseDouble(ans);

		} catch (Exception e) {
			return ERROR;
		}

		return value;
	}

	/**
	 * 
	 * read through whole file line by line, extract result
	 * 
	 * @param str
	 *            this parameter we are looking for
	 * @return a String value for this given parameter, or null if not found
	 */
	public String readSetting(String str) {
		FileInputStream filein;
		String result = null;
		try {

			filein = new FileInputStream(settingsfile);
			BufferedReader reader = new BufferedReader(new InputStreamReader(filein));
			String line = "";
			while ((line = reader.readLine()) != null) {
				String items[] = line.split(" ");
				if ((items[0].toUpperCase()).equals(str.toUpperCase())) {
					result = items[1];
				}
			}
			reader.close();
			filein.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
		
		// if setting missing due to old config file version, try to create as needed from default on demand
		if (result == null) {
			FactorySettings factory = null;
			Properties fprops = FactorySettings.createDeaults();
			try { 
				factory = FactorySettings.valueOf(str);
				result = fprops.getProperty(factory.toString());
			}
			catch (Exception e) {  }
			
		}
		if (result == null) {
			OptionalSettings optional = null;
			Properties oprops = OptionalSettings.createDeaults();
			try { 
				optional = OptionalSettings.valueOf(str); 
				result = oprops.getProperty(optional.toString());
			}
			catch (Exception e) {  }
		}
		
		return result;
	}

	/**
	 * @return the settings file in a parsed list
	 
	public synchronized static Properties getProperties() {
		Properties result = new Properties();
		try {
			FileInputStream filein = new FileInputStream(filename);
			BufferedReader reader = new BufferedReader(new InputStreamReader(filein));
			String line = "";
			while ((line = reader.readLine()) != null) {
				String items[] = line.split(" ");
				result.setProperty(items[0], items[1]);
			}
			filein.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
		return result;
	} */

	public String toString(){
		String result = new String();
		for (FactorySettings factory : FactorySettings.values()) {
			String val = readSetting(factory.toString());
			if (val != null) 
				if( ! val.equalsIgnoreCase("null"))
					result += factory.toString() + " " + val + "\r\n";
		}
	
		for (OptionalSettings ops : OptionalSettings.values()) {
			String val = readSetting(ops.toString());
			if (val != null)
				if( ! val.equalsIgnoreCase("null"))
					if( ! ops.equals(OptionalSettings.emailpassword))
						result += ops.toString() + " " + val + "\r\n";
		}
		
		return result;
	}
	
	/**
	 * Organize the settings file into 3 sections. Use Enums's to order the file
	 */
	public synchronized void writeFile(String path) {
		
		// System.out.println("writeFile(), called...");
		
		try {
			
			final String temp = System.getenv("RED5_HOME") + sep+"conf"+sep+"oculus_created.txt";
			FileWriter fw = new FileWriter(new File(temp));
			
			fw.append("# required settings \r\n");
			for (FactorySettings factory : FactorySettings.values()) {

				// over write with user's settings
				String val = readSetting(factory.toString());
				if (val != null){
					if( ! val.equalsIgnoreCase("null")){
						fw.append(factory.toString() + " " + val + "\r\n");
					}
				} else {
		
					// try reading it commented 
					if(readSetting("# " + factory.toString()) !=null)
						fw.append("# " + factory.toString());
					
				}
			}
			
			// optional
			fw.append("# manual settings \r\n");
			for (OptionalSettings ops : OptionalSettings.values()) {

				// over write with user's settings
				String val = readSetting(ops.toString());
				if (val != null){
					if( ! val.equalsIgnoreCase("null")){
						fw.append(ops.toString() + " " + val + "\r\n");
					}
				} else {
					
					// try reading it commented 
					if(readSetting("# " + ops.toString()) != null)
						fw.append("# " + ops.toString());
					
				}	
			}

			fw.append("# user list \r\n");
			fw.append("salt " + readSetting("salt") + "\r\n");

			String[][] users = getUsers();
			for (int j = 0; j < users.length; j++) {
				fw.append("user" + j + " " + users[j][0] + "\r\n");
				fw.append("pass" + j + " " + users[j][1] + "\r\n");
			}

			fw.close();
			
			// now swap temp for real file
			new File(path).delete();
			new File(temp).renameTo(new File(settingsfile));
			new File(temp).delete();

		} catch (Exception e) {
			e.printStackTrace(System.out);
		}
	}

	/**
	 * Organize the settings file into 3 sections. Use Enums's to order the file
	 */
	public synchronized void writeFile() {
		writeFile(settingsfile);
	}
	
	/**
	 * @return a list of user/pass values from the existing settings file
	 */
	public String[][] getUsers() {

		int i = 0; // count users
		for (;; i++)
			if (readSetting("user" + i) == null)
				break;

		// System.out.println("found: " + i);
		String[][] users = new String[i][2];

		for (int j = 0; j < i; j++) {
			users[j][0] = readSetting("user" + j);
			users[j][1] = readSetting("pass" + j);
		}

		return users;
	}

	/**
	 * modify value of existing settings file
	 * 
	 * @param setting
	 *            is the key to be written to file
	 * @param value
	 *            is the integer to parse into a string before being written to
	 *            file
	 */
	public void writeSettings(String setting, int value) {

		String str = null;

		try {
			str = Integer.toString(value);
		} catch (Exception e) {
			return;
		}

		if (str != null)
			writeSettings(setting, str);
	}

	/**
	 * Modify value of existing setting. read whole file, replace line while
	 * you're at it, write whole file
	 * 
	 * @param setting
	 * @param value
	 */
	public void writeSettings(String setting, String value) {
		value = value.trim();
		FileInputStream filein;
		
		// TODO: WHOA BAD, USE VECTOR 
		// what the heck is a Vector, Victor?
		// Vector<String> lines = new Vector();
		
		String[] lines = new String[999];
		try {

			filein = new FileInputStream(settingsfile);
			BufferedReader reader = new BufferedReader(new InputStreamReader(filein));
			int i = 0;
			while ((lines[i] = reader.readLine()) != null) {
				String items[] = lines[i].split(" ");
				if(items.length!=2){
					if ((items[0].toUpperCase()).equals(setting.toUpperCase())) {
						lines[i] = setting + " " + value;
					}
				}
				i++;
			}
			filein.close();
		} catch (Exception e) {
			e.printStackTrace();
		}

		FileOutputStream fileout;
		try {
			fileout = new FileOutputStream(settingsfile);
			for (int n = 0; n < lines.length; n++) {
				if (lines[n] != null) {
					new PrintStream(fileout).println(lines[n]);
				}
			}
			fileout.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	/**
	 * read whole file, add single line, write whole file
	 * 
	 * @param setting
	 * @param value
	 */
	public void newSetting(String setting, String value) {

		setting = setting.trim(); // remove trailing whitespace
		value = value.trim();

		FileInputStream filein;
		String[] lines = new String[999];
		try {
			filein = new FileInputStream(settingsfile);
			BufferedReader reader = new BufferedReader(new InputStreamReader(
					filein));
			int i = 0;
			while ((lines[i] = reader.readLine()) != null) {
				lines[i] = lines[i].replaceAll("\\s+$", "");
				if (!lines[i].equals("")) {
					i++;
				}
			}
			filein.close();
		} catch (Exception e) {
			e.printStackTrace();
		}

		FileOutputStream fileout;
		try {
			fileout = new FileOutputStream(settingsfile);
			for (int n = 0; n < lines.length; n++) {
				if (lines[n] != null) {
					new PrintStream(fileout).println(lines[n]);
				}
			}
			new PrintStream(fileout).println(setting + " " + value);
			fileout.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	public void deleteSetting(String setting) {
		// read whole file, remove offending line, write whole file
		setting = setting.replaceAll("\\s+$", ""); // remove trailing whitespace
		FileInputStream filein;
		String[] lines = new String[999];
		try {
			filein = new FileInputStream(settingsfile);
			BufferedReader reader = new BufferedReader(new InputStreamReader(
					filein));
			int i = 0;
			while ((lines[i] = reader.readLine()) != null) {
				String items[] = lines[i].split(" ");
				if ((items[0].toUpperCase()).equals(setting.toUpperCase())) {
					lines[i] = null;
				}
				i++;
			}
			filein.close();
		} catch (Exception e) {
			e.printStackTrace();
		}

		FileOutputStream fileout;
		try {
			fileout = new FileOutputStream(settingsfile);
			for (int n = 0; n < lines.length; n++) {
				if (lines[n] != null) {
					new PrintStream(fileout).println(lines[n]);
				}
			}
			fileout.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	public String readRed5Setting(String str) {
		String filenm = System.getenv("RED5_HOME") + sep+"conf"+sep+"red5.properties";
		FileInputStream filein;
		String result = null;
		try {
			filein = new FileInputStream(filenm);
			BufferedReader reader = new BufferedReader(new InputStreamReader(
					filein));
			String line = "";
			while ((line = reader.readLine()) != null) {
				String s[] = line.split("=");
				if (s[0].equals(str)) {
					result = s[1];
				}
			}
			filein.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
		return result;
	}

	public void writeRed5Setting(String setting, String value) { // modify value
																	// of
																	// existing
																	// setting
		// read whole file, replace line while you're at it, write whole file
		String filenm = System.getenv("RED5_HOME") + sep+"conf"+sep+"red5.properties";
		value = value.replaceAll("\\s+$", ""); // remove trailing whitespace
		FileInputStream filein;
		String[] lines = new String[999];
		try {
			filein = new FileInputStream(filenm);
			BufferedReader reader = new BufferedReader(new InputStreamReader(filein));
			int i = 0;
			while ((lines[i] = reader.readLine()) != null) {
				String items[] = lines[i].split("=");
				if ((items[0].toUpperCase()).equals(setting.toUpperCase())) {
					lines[i] = setting + "=" + value;
				}
				i++;
			}
			filein.close();
		} catch (Exception e) {
			e.printStackTrace();
		}

		FileOutputStream fileout;
		try {
			fileout = new FileOutputStream(filenm);
			for (int n = 0; n < lines.length; n++) {
				if (lines[n] != null) {
					new PrintStream(fileout).println(lines[n]);
				}
			}
			fileout.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	public String readSetting(OptionalSettings key) {
		return readSetting(key.toString());
	}
	
	public String readSetting(FactorySettings key) {
		return readSetting(key.toString());
	}

	public boolean getBoolean(FactorySettings setting) {
		return getBoolean(setting.toString());
	}

	public boolean getBoolean(OptionalSettings setting) {
		return getBoolean(setting.toString());
	}

	public int getInteger(OptionalSettings settings) {
		return getInteger(settings.toString());
	}	
}
