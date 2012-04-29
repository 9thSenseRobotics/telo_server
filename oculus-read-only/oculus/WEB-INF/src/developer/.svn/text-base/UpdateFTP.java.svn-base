package developer;

import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.util.Properties;

import oculus.LoginRecords;
import oculus.Observer;
import oculus.Settings;
import oculus.State;
import oculus.Util;

/** */
public class UpdateFTP implements Observer {

	// private static final int WARN_LEVEL = 40;
	
	private static State state = State.getReference();
	// private static Settings settings = new Settings();
	private static FTP ftp = new FTP();

	private String host, port, user, pass, folder;
	int i = 0;
	
	public static boolean configured(){
		File propfile = new File(Settings.ftpconfig);
		return propfile.exists();
	}

	/** Constructor */
	public UpdateFTP(){ 
		
		Properties props = new Properties();
		
		try {

			FileInputStream propFile = new FileInputStream(Settings.ftpconfig);
			props.load(propFile);
			propFile.close();
			
		} catch (Exception e) {
			return;
		}	
		
		user = (String) props.getProperty("user", System.getProperty("user.name"));
		host = (String) props.getProperty("host", "localhost");
		folder = (String) props.getProperty("folder", "telemetry");
		port = (String) props.getProperty("port", "21");
		pass = props.getProperty("password", "zdy");
		
		state.addObserver(this);
		Util.debug("starting FTP alerts...", this);
		
	}

	@Override
	public void updated(final String key) {

		Util.debug("___ftp updated checking: " + key, this);
		
		new Thread(new Runnable() {
			@Override
			public void run() {
				
				try {
					
					ftp.connect(host, port, user, pass);
					ftp.cwd(folder);
					
					ftp.storString("ip.php", state.get(State.externaladdress));
					ftp.storString("last.php", new java.util.Date().toString());
					ftp.storString("user.php", System.getProperty("user.name"));
					
					// String stats = state.toString();
					// if(stats!=null) ftp.storString("state.php", "<html><body>"+ stats.replaceAll(" : ", "<br>") + "</body</html>");
					// if(stats!=null) 
					
					ftp.storString("state.php", state.toString());

					// String log = new LoginRecords().toString();
					// if(log!=null)
						
					ftp.storString("users.php",  new LoginRecords().toString());
					
					ftp.disconnect();
					
				} catch (IOException e) {
					Util.debug(e.getLocalizedMessage(), this);
				}
				
			}
		}).start();
	
		
	}
}
