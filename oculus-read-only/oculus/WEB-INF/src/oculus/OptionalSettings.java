package oculus;

import java.util.Properties;

/** place extensions to settings here */
public enum OptionalSettings {
	
	debugenabled, emailaddress, emailpassword, developer, commandport, stopdelay, vself, offcenter, aboutface, arduinoculus, oculed;

	/** get basic settings */
	public static Properties createDeaults(){
		Properties config = FactorySettings.createDeaults();
		config.setProperty(developer.toString(), "false");
		config.setProperty(commandport.toString(), "4444");
		config.setProperty(vself.toString(), "320_240_8_85");
		return config;
	}

	/** 
	public static Properties createBasicDeveloper(){
		Properties config = FactorySettings.createDeaults();
		config.setProperty(developer.toString(), "true");
		config.setProperty(reboot.toString(), "true");
		config.setProperty(loginnotify.toString(), "true");
		return config;
	}*/
	
	/** get an gmail
	public static Properties createDeveloper(String email, String pass){
		Properties config = FactorySettings.createDeaults();
		config.setProperty(emailaddress.toString(), email);
		config.setProperty(emailpassword.toString(), pass);
		config.setProperty(emailalerts.toString(), "true");
		config.setProperty(developer.toString(), "true");
		config.setProperty(reboot.toString(), "true");
		config.setProperty(loginnotify.toString(), "true");
		return config;
	}*/
	
	/** add in settings 
	public static Properties appendDeveloper(Properties config, String email, String pass){
		config.setProperty(emailaddress.toString(), email);
		config.setProperty(emailpassword.toString(), pass);
		config.setProperty(emailalerts.toString(), "true");
		config.setProperty(developer.toString(), "true");
		return config;
	}*/
	
	/** write to file in the order set in enum 
	public static void CreateFile(FileWriter file, Properties props){
		
		// write optional 
		
	
		// write factory 
		for (FactorySettings settings : FactorySettings.values()) {
			try {
				file.append(settings.toString() + " " 
					+ props.getProperty(settings.toString()) + "\r\n");
				
			} catch (IOException e) {
				try {
					file.close();
				} catch (IOException e1) {
					e1.printStackTrace();
				}
			}
		}
	}*/
	
	
	@Override
	public String toString() {
		return super.toString();
	}
}
