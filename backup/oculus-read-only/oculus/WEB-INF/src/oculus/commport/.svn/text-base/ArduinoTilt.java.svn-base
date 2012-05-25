package oculus.commport;

import oculus.Application;
import oculus.State;
import gnu.io.CommPortIdentifier;
import gnu.io.SerialPort;
import gnu.io.SerialPortEvent;
import gnu.io.SerialPortEventListener;

public class ArduinoTilt extends AbstractArduinoComm implements SerialPortEventListener, ArduioPort {

	int pan = 90;
	int tilt = 90;
	
	public ArduinoTilt(Application app) {

		super(app);
		
		new Sender(new byte[]{ 'e', '1' });
		new Sender(new byte[]{ 'v', (byte) pan });
		new Sender(new byte[]{ 't', (byte) tilt });
		new Sender(new byte[]{ 'o', (byte) tilt });

//		app.playerCallServer(PlayerCommands.dock, "undock");

	}

	public void connect(){
		try {

			serialPort = (SerialPort) CommPortIdentifier.getPortIdentifier(
					state.get(State.serialport)).open(
					AbstractArduinoComm.class.getName(), SETUP);
			serialPort.setSerialPortParams(115200, SerialPort.DATABITS_8,
					SerialPort.STOPBITS_1, SerialPort.PARITY_NONE);

			// open streams
			out = serialPort.getOutputStream();
			in = serialPort.getInputStream();

			// register for serial events
			serialPort.addEventListener(this);
			serialPort.notifyOnDataAvailable(true);

		} catch (Exception e) {
			return;
		}
	}

	public void serialEvent(SerialPortEvent event) {
		if (event.getEventType() == SerialPortEvent.DATA_AVAILABLE) {
			manageInput();
		}
	}
	
	@Override
	public void execute() {
		String response = "";
		for (int i = 0; i < buffSize; i++)
			response += (char) buffer[i];

		System.out.println("__ " + "in: " + response);

		// take action as arduino has just turned on
		if (response.equals("reset")) {
			isconnected = true;
			version = null;
			new Sender(GET_VERSION);
			updateSteeringComp();
		} else if (response.startsWith("version:")) {
			if (version == null) {
				// get just the number
				version = response.substring(response.indexOf("version:") + 8, response.length());
				application.message("oculusTilt: " + version, null, null);
			} 
		} else if (response.charAt(0) != GET_VERSION[0]) {
			// don't bother showing watch dog pings to user screen
			application.message("oculusTilt: " + response, null, null);
		}
	}
	
	@Override
	public void turnRight() {
		new Sender(new byte[]{ 'l' }); 
		//if (application.muteROVonMove) {
		//	application.muteROVMic();
		//}
		
	}

	@Override
	public void turnLeft() {
		new Sender(new byte[] { 'r'});
		
		//if (application.muteROVonMove) {
		//	application.muteROVMic();
		//}
	}
	
	@Override
	public void nudge(final String direction){
	
		System.out.println("Nudge: " + direction);

		new Sender(new byte[] {'f'});
		
	}
	
	@Override
	public void goForward() {	
		
		new Sender(new byte[] { 'u' }); 
		
	}

	@Override
	public void goBackward() {

		new Sender(new byte[] { 'd' } ); // , (byte) tilt });
		
	}

	@Override
	public void camGo() {}

	@Override
	public void camCommand(String str) {}

	@Override
	public void camHoriz() {}

	@Override
	public void camToPos(Integer n) {}

	@Override
	public void speedset(String str){}

	@Override
	public void slide(final String dir) {
		
		System.out.println("slide: " + dir);
		new Sender(new byte[]{'o'});

	}

	@Override
	public void slidecancel() {}

	@Override
	public Integer clickSteer(String str) {
		
		System.out.println("_click steer: " + str);
		String[] val = str.split(" ");
		double x = Double.parseDouble(val[0]);
		double y = Integer.parseInt(val[1]);
		
		if(y<-10){
			new Sender(new byte[]{'u'});
			if(y<-120) new Sender(new byte[]{'u'});
			if(y<-200) new Sender(new byte[]{'u'});
		} else if(y>10){
			new Sender(new byte[] {'d'}); 
			if(y>120) new Sender(new byte[] {'d'}); 
			if(y>200) new Sender(new byte[] {'d'}); 
		}
		
		if(x>10){
			new Sender(new byte[]{'l'});
			if(x>120) new Sender(new byte[]{'l'});
			if(x>200) new Sender(new byte[]{'l'});
		} else if(x<-10){
			new Sender(new byte[] {'r'}); 
			if(x<-120) new Sender(new byte[] {'r'}); 
			if(x<-200) new Sender(new byte[] {'r'}); 
		}
		
		return 0;
	}

	@Override
	public void clickNudge(Integer x) {}

	@Override
	public Integer clickCam(Integer y) {
		return null;
	}

	@Override
	public void releaseCameraServo() {}

	@Override
	public void updateSteeringComp() {}

	@Override
	public void stopGoing() {
		
//		app.playerCallServer(PlayerCommands.dock, "undock");


		new Sender(new byte[] { 'o' });

	}
} 

