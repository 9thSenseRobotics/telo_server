package oculus.commport;

import gnu.io.SerialPortEvent;

public interface ArduioPort {

	public static final long DEAD_TIME_OUT = 30000;
	public static final int SETUP = 2000;
	public static final int WATCHDOG_DELAY = 5000;
	public static final byte FORWARD = 'f';
	public static final byte BACKWARD = 'b';
	public static final byte LEFT = 'l';
	public static final byte RIGHT = 'r';
	public static final byte COMP = 'c';
	public static final byte CAM = 'v';
	public static final byte ECHO = 'e';
	public static final byte[] SONAR = { 'd' };
	public static final byte[] STOP = { 's' };
	public static final byte[] GET_VERSION = { 'y' };
	public static final byte[] CAMRELEASE = { 'w' };
	public static final byte[] ECHO_ON = { 'e', '1' };
	public static final byte[] ECHO_OFF = { 'e', '0' };

	/** open port, enable read and write, enable events */
	public abstract void connect();

	/** @return True if the serial port is open */
	public abstract boolean isConnected();

	public abstract void serialEvent(SerialPortEvent event);

	public abstract void execute();

	
	/** @return the time since last write() operation */
	public abstract long getWriteDelta();

	/** @return this device's firmware version */
	public abstract String getVersion();

	/** @return the time since last read operation */
	public abstract long getReadDelta();

	/**
	 * @param update
	 *            is set to true to turn on echo'ing of serial commands
	 */
	public abstract void setEcho(boolean update);

	public abstract void reset();

	/** */
	public abstract void stopGoing();

	/** */
	public abstract void goForward();

	/** */
	public abstract void goBackward();

	/** */
	public abstract void turnRight();

	/** */
	public abstract void turnLeft();

	public abstract void camGo();

	public abstract void camCommand(String str);

	/** level the camera servo */
	public abstract void camHoriz();

	public abstract void camToPos(Integer n);

	/** Set the speed on the bot */
	public abstract void speedset(String str);

	public abstract void nudge(String dir);

	public abstract void slide(String dir);

	public abstract void slidecancel();

	public abstract Integer clickSteer(String str);

	public abstract void clickNudge(Integer x);

	public abstract Integer clickCam(Integer y);

	/** turn off the servo holding the mirror */
	public abstract void releaseCameraServo();

	/** send steering compensation values to the arduino */
	public abstract void updateSteeringComp();	
}