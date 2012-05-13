package oculus.commport;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.Enumeration;
import java.util.Vector;

import oculus.Application;
import oculus.OptionalSettings;
import oculus.Settings;
import oculus.State;
import oculus.Util;

import gnu.io.*;

public class Discovery implements SerialPortEventListener {

	private static State state = State.getReference();
	private static Settings settings = new Settings();

	/* serial port configuration parameters */
	public static final int[] BAUD_RATES = { 57600, 115200 };
	public static final int TIMEOUT = 2000;
	public static final int DATABITS = SerialPort.DATABITS_8;
	public static final int STOPBITS = SerialPort.STOPBITS_1;
	public static final int PARITY = SerialPort.PARITY_NONE;
	public static final int FLOWCONTROL = SerialPort.FLOWCONTROL_NONE;

	/* add known devices here, strings returned from the firmware */
	public static final String OCULUS_TILT = "oculusTilt";
	public static final String OCULUS_SONAR = "oculusSonar";
	public static final String OCULUS_DC = "oculusDC";
	public static final String LIGHTS = "L";
	public static final long RESPONSE_DELAY = 1000;

	/* reference to the underlying serial port */
	private static SerialPort serialPort = null;
	private static InputStream inputStream = null;
	private static OutputStream outputStream = null;

	/* list of all free ports */
	private static Vector<String> ports = new Vector<String>();

	/* read from device */
	private static byte[] buffer = null; 
	
	/* constructor makes a list of available ports */
	public Discovery() {
		
		getAvailableSerialPorts();
		
		if(ports.size()==0){
			Util.log("no serial ports found", this);
			return;
		}
		
		Util.debug("discovery starting on: " + ports.size() + " ports", this);
		for(int i = ports.size() - 1; i >= 0; i--) 
			Util.debug("[" + i + "] port name: " + ports.get(i), this);
		
		String motors = settings.readSetting(OptionalSettings.arduinoculus);
		if(motors == null){			
			searchMotors(); 
		} else {			
			Util.debug("skipping discovery, found motors on: " + motors, this);
			state.set(State.serialport, motors);
			state.set(State.firmware, OCULUS_DC);
		}
		
		String lights = settings.readSetting(OptionalSettings.oculed);
		if(lights == null){
			searchLights();	
		} else {
			Util.debug("skipping discovery, found lights on: " + lights, this);
			state.set(State.lightport, lights);
		}
	
		//validate();
		// state.();
	}
	
	/** */
	private static String getPortName(){
		
		String name = "";
		String com = serialPort.getName();
		
		//TODO: get a port name, or full device path for linux 
		if(Settings.os.equals("linux")) return com;
		else for(int i = 0 ; i < com.length();i++)
			if(com.charAt(i) != '/' && com.charAt(i) != '.')
				name += com.charAt(i);
		
		return name;
	}
	
	/** */
	private void validate(){
		if (state.get(State.firmware) == null){
			Util.debug("No motors detected, try again..", this);
			searchMotors();
		}
		if (state.get(State.serialport) == null){
			Util.debug("No motors detected, try again..", this);
			searchMotors();
		}
		if (state.get(State.lightport) == null){ 
			Util.debug("NO lights detected, try again..", this);
			searchLights();
		}
		
		// show state if problems 
		if (state.get(State.firmware) == null || state.get(State.serialport) == null || state.get(State.lightport) == null) {
			Util.debug("..can't find motors and/or lights", this);
			Util.debug(state.toString(), this);
		}
	}
	
	/** */
	private static void getAvailableSerialPorts() {
		ports.clear();
		@SuppressWarnings("rawtypes")
		Enumeration thePorts = CommPortIdentifier.getPortIdentifiers();
		while (thePorts.hasMoreElements()) {
			CommPortIdentifier com = (CommPortIdentifier) thePorts.nextElement();
			if (com.getPortType() == CommPortIdentifier.PORT_SERIAL) ports.add(com.getName());
		}
	}

	/** connects on start up, return true is currently connected */
	private boolean connect(final String address, final int rate) {

		Util.debug("try to connect to: " + address + " buad:" + rate, this);

		try {

			/* construct the serial port */
			serialPort = (SerialPort) CommPortIdentifier.getPortIdentifier(address).open("Discovery", TIMEOUT);

			/* configure the serial port */
			serialPort.setSerialPortParams(rate, DATABITS, STOPBITS, PARITY);
			serialPort.setFlowControlMode(FLOWCONTROL);

			/* extract the input and output streams from the serial port */
			inputStream = serialPort.getInputStream();
			outputStream = serialPort.getOutputStream();

			// register for serial events
			serialPort.addEventListener(this);
			serialPort.notifyOnDataAvailable(true);
			
		} catch (Exception e) {
			Util.log("error connecting to: " + address, this);
			close();
			return false;
		}

		// be sure
		if (inputStream == null) return false;
		if (outputStream == null) return false;

		Util.log("connected: " + address + " buad:" + rate, this);
		
		return true;
	}

	/** Close the serial port streams */
	private void close() {
		
		///TODO: serialPort.removeEventListener();
		
		if (serialPort != null) {
			Util.log("close port: " + serialPort.getName() + " baud: " + serialPort.getBaudRate());
			serialPort.close();
			serialPort = null;
		}
		try {
			if (inputStream != null) inputStream.close();
		} catch (Exception e) {
			Util.log("input stream close():" + e.getMessage(), this);
		}
		try {
			if (outputStream != null) outputStream.close();
		} catch (Exception e) {
			Util.log("output stream close():" + e.getMessage(), this);
		}
		
		buffer = null;
	}

	/** Loop through all available serial ports and ask for product id's */
	public void searchLights() {
	
		// try to limit searching 
		if(state.get(State.serialport)!=null) 
			if(ports.contains(state.get(State.serialport)))
				ports.remove(state.get(State.serialport));
			
		Util.debug("discovery for lights starting on: " + ports.size(), this);
		
		for (int i = ports.size() - 1; i >= 0; i--) {
			if (connect(ports.get(i), BAUD_RATES[0])) {	
				Util.delay(TIMEOUT*2);
				close();
			}
		}
	}
	
	/** Loop through all available serial ports and ask for product id's */
	public void searchMotors() {
			
		// try to limit searching 
		String motors = settings.readSetting(OptionalSettings.oculed);
		if(motors!=null){
			Util.debug("removing lights port:" + ports.toString(), this);
			ports.remove(motors);
		}
		
		Util.debug("discovery for motors starting on: " + ports.size(), this); 
	
		for (int i = ports.size() - 1; i >= 0; i--) {
			if (connect(ports.get(i), BAUD_RATES[1])) {				
				Util.delay(TIMEOUT*2);
				close();
			}
		}
	}
	
	/** check if this is a known derive, update in state */
	public void lookup(String id){	
		
		if (id == null) return;
		if (id.length() == 0) return;
		id = id.trim();
		
		Util.debug("is a product?? [" + id + "] length: " + id.length(), this);

		if (id.length() == 1 ){
			if(id.equals(LIGHTS)){		
				state.set(State.lightport, getPortName());
				Util.debug("found lights on comm port: " +  getPortName(), this);		
			}
			
			return;
		} 
			
		if(id.startsWith("id")){	
			
			id = id.substring(2, id.length());
				
			Util.debug("found product id[" + id + "] on comm port: " +  getPortName(), this);

			if (id.equalsIgnoreCase(OCULUS_DC)) {

				state.set(State.serialport, getPortName());
				state.set(State.firmware, OCULUS_DC);
				
			} else if (id.equalsIgnoreCase(OCULUS_SONAR)) {

				state.set(State.serialport, getPortName());
				state.set(State.firmware, OCULUS_SONAR);	
			
			} else if (id.equalsIgnoreCase(OCULUS_TILT)) {

				state.set(State.serialport, getPortName());
				state.set(State.firmware, OCULUS_TILT);
				
			}

			//TODO: other devices here if grows
			
		}
	}
	
	/** send command to get product id */
	public void getProduct() {
		try {
			inputStream.skip(inputStream.available());
		} catch (IOException e) {
			Util.log(e.getStackTrace().toString(),this);
			return;
		}
		try {
			outputStream.write(new byte[] { 'x', 13 });
		} catch (IOException e) {
			Util.log(e.getStackTrace().toString(),this);
			return;
		}

		// wait for reply
		Util.delay(RESPONSE_DELAY);
	}

	@Override
	public void serialEvent(SerialPortEvent arg0) {
	
		Util.debug("_event: " + arg0,this);
		
		if(buffer!=null){
			Util.log("...too much serial?",this);
			return;
		}
		
		// don't fire again 
		// serialPort.removeEventListener();
	
		byte[] buffer = new byte[32];
		
		getProduct();
		
		String device = new String();
		int read = 0;
		try {
			
			read = inputStream.read(buffer);
			
		} catch (IOException e) {
			Util.log(e.getStackTrace().toString(),this);
		}
		
		// read buffer 
		for (int j = 0; j < read; j++) {
			if(Character.isLetter((char) buffer[j]))
				device += (char) buffer[j];
		}
		
		Util.debug("_lookup: " + device, this);
		
		lookup(device);
	}

	/** match types of firmware names and versions */
	public AbstractArduinoComm getMotors(Application application) {
		// TODO Auto-generated method stub
		
		
		
		//new ArduinoCommDC(this);
		// create matching class based on firmware
		// Todo: state.equals(state.firmware, DiscoveryOc...); 
		/*
		if (state.get(State.firmware).equals(Discovery.OCULUS_SONAR)){
			comport = new oculus.commport.ArduinoCommSonar(this);
		} else if (state.get(State.firmware).equals(Discovery.OCULUS_TILT)){
			comport = new oculus.commport.ArduinoTilt(this);
		} else {
		*/
		
		// comport = new ArduinoCommDC(this);
		
		//}
		
		
		return new ArduinoCommDC(application);
	}

	/** manage types of ights here */
	public LightsComm getLights(Application application) {
		return new LightsComm(application);
	}
}