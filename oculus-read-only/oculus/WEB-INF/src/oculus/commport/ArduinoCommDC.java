package oculus.commport;

import oculus.Application;
import oculus.State;
import oculus.Util;

import gnu.io.CommPortIdentifier;
import gnu.io.SerialPort;
import gnu.io.SerialPortEvent;
import gnu.io.SerialPortEventListener;

public class ArduinoCommDC extends AbstractArduinoComm implements SerialPortEventListener, ArduioPort {
	
	public ArduinoCommDC(Application app) {
		super(app);

		// check for lost connection
		new WatchDog().start();
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
			Util.log("could NOT connect to the motors on: " + state.get(State.serialport), this);
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

		// System.out.println("in: " + response);

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
				application.message("oculusDC: " + version, null, null);
			} 
		} else if (response.charAt(0) != GET_VERSION[0]) {
			// don't bother showing watch dog pings to user screen
			application.message("oculusDC: " + response, null, null);
		}
	}
}