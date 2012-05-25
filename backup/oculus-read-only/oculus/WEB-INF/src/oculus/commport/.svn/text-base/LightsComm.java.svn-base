package oculus.commport;

import gnu.io.CommPortIdentifier;
import gnu.io.SerialPort;
import gnu.io.SerialPortEvent;
import gnu.io.SerialPortEventListener;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.TooManyListenersException;

import developer.SendMail;

import oculus.Application;
import oculus.State;
import oculus.Util;

public class LightsComm implements SerialPortEventListener {

	private State state = State.getReference();
	public static final long DEAD_MAN_TIME_OUT = 20000;
	public static final long USER_TIME_OUT =  60000;
	public static final int SETUP = 2000;
	public static final int BAUD_RATE = 57600;
	public static final byte GET_PRODUCT = 'x';
	public static final byte GET_VERSION = 'y';
	private static final byte DOCK_ON = 'o';
	private static final byte DOCK_OFF = 'f';
	public static final byte SPOT_OFF = 'a';
	public static final byte SPOT_1 = 'b';
	public static final byte SPOT_2 = 'c';
	public static final byte SPOT_3 = 'd';
	public static final byte SPOT_4 = 'e';
	public static final byte SPOT_5 = 'f';
	public static final byte SPOT_6 = 'g';
	public static final byte SPOT_7 = 'h';
	public static final byte SPOT_8 = 'i';
	public static final byte SPOT_9 = 'j';
	public static final byte SPOT_MAX = 'k';

	private SerialPort serialPort = null;
	private InputStream in= null;
	private OutputStream out= null;
	
	// will be discovered from the device 
	protected String version = null;

	// track write times
	private long lastSent = System.currentTimeMillis();
	private long lastRead = System.currentTimeMillis();

	// make sure all threads know if connected 
	private boolean isconnected = false;
	
	private int spotLightBrightness = 0;
	private boolean floodLightOn = false;
	
	// private static long z = 0;
	private static long lastUserCommand = 0;
	
	// call back
	private Application application = null;
	
	int i = 0;

	/**
	 * Constructor but call connect to configure
	 * 
	 * @param app 
	 * 			  is the main oculus application, we need to call it on
	 * 			Serial events like restet            
	 */
	public LightsComm(Application app) {
		application = app; 
		
		if( state.get(State.lightport) != null ){
			new Thread(new Runnable() { 
				public void run() {
					connect();				
					Util.delay(SETUP);
				}	
			}).start();
			
			new WatchDog().start();
		}	
	}
	
	/** open port, enable read and write, enable events */
	public void connect() {
		try {

			serialPort = (SerialPort)CommPortIdentifier.getPortIdentifier(
					state.get(State.lightport)).open(LightsComm.class.getName(), SETUP);
			serialPort.setSerialPortParams(BAUD_RATE, SerialPort.DATABITS_8, SerialPort.STOPBITS_1, SerialPort.PARITY_NONE);

			// open streams
			out = serialPort.getOutputStream();
			in = serialPort.getInputStream();
			
		} catch (Exception e) {
			Util.log("could NOT connect to the the lights on:" + state.get(State.lightport), this);
			return;
		}
		
		
		// register for serial events
		try {
			serialPort.addEventListener(this);
		} catch (TooManyListenersException e) {
			Util.log(e.getMessage(), this);
		}
		serialPort.notifyOnDataAvailable(true);
		isconnected = true;	
		
		Util.log("connected to the the lights on:" + state.get(State.lightport), this);
	
	}

	/** @return True if the serial port is open */
	public boolean isConnected(){
		return isconnected;
	}
	
	public int spotLightBrightness() {
		return spotLightBrightness;
	}
	
	public boolean floodLightOn() {
		return floodLightOn;
	}
	
	@Override
	/**
	 */
	public void serialEvent(SerialPortEvent event) {
		if (event.getEventType() == SerialPortEvent.DATA_AVAILABLE) {
			try {
				byte[] input = new byte[32];
				
				int read = in.read(input);
				
				String str = "";
				for (int j = 0; j < read; j++) str += (char) input[j];
						Util.log("_ input read delta: " + getReadDelta() + " read[" + str.trim() + "]", this);
				
				lastRead = System.currentTimeMillis();
			
			} catch (IOException e) {
				Util.log("event : " + e.getMessage(), this);
			}
		}
	}	
	
	
	/** inner class to check if getting responses in timely manor */
	public class WatchDog extends Thread {
		public WatchDog() {
			this.setDaemon(true);
		}

		public void run() {
			Util.delay(SETUP);
			while (true) {
				
			
				Util.debug("read delta: " + getReadDelta() + " write delta: " + getWriteDelta(), this);
				
				if((System.currentTimeMillis() - lastUserCommand) > USER_TIME_OUT){
					if(floodLightOn || (spotLightBrightness>0)){
						application.message("lights on too long", null, null);
						sendCommand(SPOT_OFF);
						sendCommand(DOCK_OFF);
						sendCommand(SPOT_OFF);
						sendCommand(DOCK_OFF);
						// TODO: check input and set these flags!
						floodLightOn = false;
						spotLightBrightness = 0;
						lastUserCommand = System.currentTimeMillis();	
					}
				}
				
				// refresh values
				if(getReadDelta() > (DEAD_MAN_TIME_OUT/2)){
					
					Util.debug("spotLightBrightness = " + spotLightBrightness, this);
					
					if(floodLightOn) sendCommand(DOCK_ON);
					else if(!floodLightOn) sendCommand(DOCK_OFF);
					
					if(spotLightBrightness==0) sendCommand((byte) SPOT_OFF);
					else if(spotLightBrightness==10)sendCommand((byte) SPOT_1);
					else if(spotLightBrightness==20) sendCommand((byte) SPOT_2);
					else if(spotLightBrightness==30) sendCommand((byte) SPOT_3); 
					else if(spotLightBrightness==40) sendCommand((byte) SPOT_4);
					else if(spotLightBrightness==50) sendCommand((byte) SPOT_5);
					else if(spotLightBrightness==60) sendCommand((byte) SPOT_6);
					else if(spotLightBrightness==70) sendCommand((byte) SPOT_7);
					else if(spotLightBrightness==80) sendCommand((byte) SPOT_8);
					else if(spotLightBrightness==90) sendCommand((byte) SPOT_9);
					else if(spotLightBrightness==100) sendCommand((byte) SPOT_MAX);
					lastUserCommand = System.currentTimeMillis();		
					
				}
				
				// error state
				if(getReadDelta() > DEAD_MAN_TIME_OUT){
					
					application.message("lights failure, time out!", null, null);
					Util.debug("_+_lights failure, time out!", this);
					disconnect();
					
					new SendMail("lights error", "lights failure, time out, disconnecting" + " read delta: " + getReadDelta() + " write delta: " + getWriteDelta());
					return; 
				}
				
				Util.delay(SETUP);
				
			}		
		}
	}

	/** @return the time since last write() operation */
	public long getWriteDelta() {
		return System.currentTimeMillis() - lastSent;
	}

	/** @return this device's firmware version */
	public String getVersion(){
		return version;
	}
	
	/** @return the time since last read operation */
	public long getReadDelta() {
		return System.currentTimeMillis() - lastRead;
	}

	/** inner class to send commands */
	private class Sender extends Thread {		
		private byte command = 13;
		public Sender(final byte cmd) {
			command = cmd;
			if(isConnected())start();
		}
		public void run() {
			try {
				out.write(command);
			} catch (Exception e) {
				Util.debug(e.getMessage(), this);
				reset();
			}
			lastSent = System.currentTimeMillis();
			
			Util.debug( i++ + " send: " + (char)command, this);
		}
	}

	
	public void reset(){
		if (isconnected) {
			new Thread(new Runnable() { 
				public void run() {
					disconnect();
					connect();
				}
			}).start();
		}
	}

	/** shutdown serial port */
	protected void disconnect() {
		try {
			in.close();
			out.close();
			isconnected = false;
		} catch (Exception e) {
			System.out.println("close(): " + e.getMessage());
		}
		serialPort.close();
	}

	/**
	 * Send a multi byte command to send the arduino 
	 * 
	 * @param command
	 *            is a byte array of messages to send
	*/
	private synchronized void sendCommand(final byte command) {
		
		if(!isconnected) return;
		
		new Sender(command);

		// track last write
		lastSent = System.currentTimeMillis();
	} 

	public synchronized void setSpotLightBrightness(int target){
		
		if( !isConnected()){
			Util.log("lights NOT found", this);
			return;
		}
		
		Util.log("set spot:" + target, this);
		
		if(target==0) sendCommand((byte) SPOT_OFF);
		else if(target==10)sendCommand((byte) SPOT_1);
		else if(target==20) sendCommand((byte) SPOT_2);
		else if(target==30) sendCommand((byte) SPOT_3); 
		else if(target==40) sendCommand((byte) SPOT_4);
		else if(target==50) sendCommand((byte) SPOT_5);
		else if(target==60) sendCommand((byte) SPOT_6);
		else if(target==70) sendCommand((byte) SPOT_7);
		else if(target==80) sendCommand((byte) SPOT_8);
		else if(target==90) sendCommand((byte) SPOT_9);
		else if(target==100) sendCommand((byte) SPOT_MAX);
		
		spotLightBrightness = target;
		application.message("spotlight brightness set to "+target+"%", "light", Integer.toString(spotLightBrightness));
		lastUserCommand = System.currentTimeMillis();
		
	}
	
	public synchronized void floodLight(String str){
		if( !isConnected()){
			Util.log("lights NOT found", this);
			application.message("lights not found", null, null);
			return;
		}
		if (str.equals("on")) { 
			sendCommand(DOCK_ON);
			floodLightOn = true;
		}
		else { 
			sendCommand(DOCK_OFF);
			floodLightOn = false; 
		}
		
		application.message("floodlight "+str, null, null);
		lastUserCommand = System.currentTimeMillis();
		
	}
}