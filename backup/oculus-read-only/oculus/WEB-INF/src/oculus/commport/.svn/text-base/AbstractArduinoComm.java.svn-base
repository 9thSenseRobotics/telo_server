package oculus.commport;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;

import oculus.Application;
import oculus.FactorySettings;
import oculus.Settings;
import oculus.State;
import oculus.Util;

import gnu.io.SerialPort;

public abstract class AbstractArduinoComm implements ArduioPort {

	protected State state = State.getReference();
	protected SerialPort serialPort = null;
	protected InputStream in;
	protected OutputStream out;
	protected String version = null;
	protected byte[] buffer = new byte[32];
	protected int buffSize = 0;
	protected long lastSent = System.currentTimeMillis();
	protected long lastRead = System.currentTimeMillis();
	protected Settings settings;
	protected int tempspeed = 999;
	protected int clicknudgedelay = 0;
	protected String tempstring = null;
	protected int tempint = 0;
	protected volatile boolean isconnected = false;
	protected Application application = null;
	
	public int speedslow;
	public int speedmed;
	public int camservohoriz;
	public int camposmax;
	public int camposmin;
	public int nudgedelay;
	public int maxclicknudgedelay;
	public int maxclickcam;
	public double clicknudgemomentummult;
	public int steeringcomp;
	public boolean holdservo;
	
	public int camservodirection = 0;
	public int camservopos = camservohoriz;
	public int camwait = 400;
	public int camdelay = 50;
	public int speedfast = 255;
	public int turnspeed = 255;
	public int speed = speedfast;
	protected String direction = null;
	public boolean moving = false;
	public volatile boolean sliding = false;
	public volatile boolean movingforward = false;

	public AbstractArduinoComm(Application app) {

		application = app;
		settings = new Settings();
		
		speedslow = settings.getInteger("speedslow");
		speedmed = settings.getInteger("speedmed");
		camservohoriz = settings.getInteger("camservohoriz");
		camposmax = settings.getInteger("camposmax");
		camposmin = settings.getInteger("camposmin");
		nudgedelay = settings.getInteger("nudgedelay");
		maxclicknudgedelay = settings.getInteger("maxclicknudgedelay");
		maxclickcam = settings.getInteger("maxclickcam");
		clicknudgemomentummult = settings.getDouble("clicknudgemomentummult");
		steeringcomp = settings.getInteger("steeringcomp");
		holdservo = settings.getBoolean(FactorySettings.holdservo.toString());
		
		if (state.get(State.serialport) != null) {
			new Thread(new Runnable() {
				public void run() {
					connect();
					Util.delay(SETUP);
					byte[] cam = { CAM, (byte) camservopos };
					sendCommand(cam);
					Util.delay(camwait);
					sendCommand(CAMRELEASE);
				}
			}).start();
		}
	}

	/** inner class to send commands as a separate thread each */
	class Sender extends Thread {
		private byte[] command = null;
		public Sender(final byte[] cmd) {
			if (!isconnected){
			
				// TAKE IT DOWN! 
				if(state.get(oculus.State.firmware) == null ){ // .equals(oculus.State.unknown)){
					if(state.getBoolean(oculus.State.developer)){
						System.out.println("OCULUS: AbstractArduinoComm, not connected, rebooting");		
						Util.systemCall("shutdown -r -f -t 01");	
					}
				}
				
			} else {
				command = cmd;
				this.start();
			}
		}

		public void run() {
			sendCommand(command);
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

				if (getReadDelta() > DEAD_TIME_OUT) {
					System.out.println("OCULUS: AbstractArduinoComm.WatchDog(), "
							+"arduino watchdog time out, may be no hardware attached");
					
					state.set(oculus.State.commwatchdog, true);
					
					return; // die, no point living?
				}

				if (getReadDelta() > WATCHDOG_DELAY) {
					new Sender(GET_VERSION);
					Util.delay(WATCHDOG_DELAY);
				}
			}		
		}
	}

	@Override
	public abstract void connect();
	
	@Override
	public boolean isConnected() {
		return isconnected;
	}

	public abstract void execute(); 
	
	/** */
	public void manageInput(){
		try {
			byte[] input = new byte[32];
			int read = in.read(input);
			for (int j = 0; j < read; j++) {
				// print() or println() from arduino code
				if ((input[j] == '>') || (input[j] == 13)
						|| (input[j] == 10)) {
					// do what ever is in buffer
					if (buffSize > 0)
						execute();
					// reset
					buffSize = 0;
					// track input from arduino
					lastRead = System.currentTimeMillis();
				} else if (input[j] == '<') {
					// start of message
					buffSize = 0;
				} else {
					// buffer until ready to parse
					buffer[buffSize++] = input[j];
				}
			}
		} catch (IOException e) {
			System.out.println("event : " + e.getMessage());
		}
	}
	
	@Override
	public long getWriteDelta() {
		return System.currentTimeMillis() - lastSent;
	}

	@Override
	public String getVersion() {
		return version;
	}

	@Override
	public long getReadDelta() {
		return System.currentTimeMillis() - lastRead;
	}

	@Override
	public void setEcho(boolean update) {
		if (update) new Sender(ECHO_ON);
		else new Sender(ECHO_OFF);
	}

	@Override
	public void reset() {
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
			version = null;
		} catch (Exception e) {
			System.out.println("disconnect(): " + e.getMessage());
		}
		serialPort.close();
	}

	/**
	 * Send a multi byte command to send the arduino
	 * 
	 * @param command
	 *            is a byte array of messages to send
	 */
	protected synchronized void sendCommand(final byte[] command) {

		if (!isconnected)
			return;

		try {

			// send
			out.write(command);

			// end of command
			out.write(13);

		} catch (Exception e) {
			reset();
			System.out.println("OCULUS: sendCommand(), " + e.getMessage());
		}

		// track last write
		lastSent = System.currentTimeMillis();
	}

	@Override
	public void stopGoing() {

		if (application.muteROVonMove && moving) {
			application.unmuteROVMic();
		}

		new Sender(STOP);
		moving = false;
		movingforward = false;
	}

	@Override
	public void goForward() {
		new Sender(new byte[] { FORWARD, (byte) speed });
		moving = true;
		movingforward = true;

		if (application.muteROVonMove) {
			application.muteROVMic();
		}
	}

	@Override
	public void goBackward() {
		new Sender(new byte[] { BACKWARD, (byte) speed });
		moving = true;
		movingforward = false;

		if (application.muteROVonMove) {
			application.muteROVMic();
		}
	}

	@Override
	public void turnRight() {
		int tmpspeed = turnspeed;
		int boost = 10;
		if (speed < turnspeed && (speed + boost) < speedfast)
			tmpspeed = speed + boost;

		new Sender(new byte[] { RIGHT, (byte) tmpspeed });
		moving = true;

		if (application.muteROVonMove) {
			application.muteROVMic();
		}
	}

	@Override
	public void turnLeft() {
		int tmpspeed = turnspeed;
		int boost = 10;
		if (speed < turnspeed && (speed + boost) < speedfast)
			tmpspeed = speed + boost;

		new Sender(new byte[] { LEFT, (byte) tmpspeed });
		moving = true;

		if (application.muteROVonMove) {
			application.muteROVMic();
		}
	}
	
	@Override
	public void camGo() {
		new Thread(new Runnable() {
			public void run() {
				while (camservodirection != 0) {
					sendCommand(new byte[] { CAM, (byte) camservopos });
					Util.delay(camdelay);
					camservopos += camservodirection;
					if (camservopos > camposmax) {
						camservopos = camposmax;
						camservodirection = 0;
					}
					if (camservopos < camposmin) {
						camservopos = camposmin;
						camservodirection = 0;
					}
				}

				checkForHoldServo();
			}
		}).start();
	}

	@Override
	public void camCommand(String str) {
		if (str.equals("stop")) {
			camservodirection = 0;
		} else if (str.equals("up")) {
			camservodirection = 1;
			camGo();
		} else if (str.equals("down")) {
			camservodirection = -1;
			camGo();
		} else if (str.equals("horiz")) {
			camHoriz();
		} else if (str.equals("downabit")) {
			camservopos -= 5;
			if (camservopos < camposmin) {
				camservopos = camposmin;
			}
			new Thread(new Runnable() {
				public void run() {
					sendCommand(new byte[] { CAM, (byte) camservopos });
					checkForHoldServo();
				}
			}).start();
		} else if (str.equals("upabit")) {
			camservopos += 5;
			if (camservopos > camposmax) {
				camservopos = camposmax;
			}
			new Thread(new Runnable() {
				public void run() {
					sendCommand(new byte[] { CAM, (byte) camservopos });
					checkForHoldServo();
				}
			}).start();
		}
		// else if (str.equals("hold")) {
		// new Thread(new Runnable() { public void run() {
		// sendCommand(new byte[] { CAM, (byte) camservopos });
		// } }).start();
		// }
	}

	@Override
	public void camHoriz() {
		camservopos = camservohoriz;
		new Thread(new Runnable() {
			public void run() {
				try {
					byte[] cam = { CAM, (byte) camservopos };
					sendCommand(cam);
					checkForHoldServo();

				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
	}

	@Override
	public void camToPos(Integer n) {
		camservopos = n;
		new Thread(new Runnable() {
			public void run() {
				try {
					sendCommand(new byte[] { CAM, (byte) camservopos });
					checkForHoldServo();
				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
	}

	@Override
	public void speedset(String str) {
		if (str.equals("slow")) {
			speed = speedslow;
		}
		if (str.equals("med")) {
			speed = speedmed;
		}
		if (str.equals("fast")) {
			speed = speedfast;
		}
		if (movingforward) {
			goForward();
		}
	}

	@Override
	public void nudge(String dir) {
		direction = dir;
		new Thread(new Runnable() {
			public void run() {
				int n = nudgedelay;
				if (direction.equals("right")) {
					turnRight();
				}
				if (direction.equals("left")) {
					turnLeft();
				}
				if (direction.equals("forward")) {
					goForward();
					movingforward = false;
					n *= 4;
				}
				if (direction.equals("backward")) {
					goBackward();
					n *= 4;
				}

				Util.delay(n);

				if (movingforward == true) {
					goForward();
				} else {
					stopGoing();
				}
			}
		}).start();
	}

	@Override
	public void slide(String dir) {
		if (sliding == false) {
			sliding = true;
			direction = dir;
			tempspeed = 999;
			new Thread(new Runnable() {
				public void run() {
					try {
						int distance = 300;
						int turntime = 500;
						tempspeed = speed;
						speed = speedfast;
						if (direction.equals("right")) {
							turnLeft();
						} else {
							turnRight();
						}
						Thread.sleep(turntime);
						if (sliding == true) {
							goBackward();
							Thread.sleep(distance);
							if (sliding == true) {
								if (direction.equals("right")) {
									turnRight();
								} else {
									turnLeft();
								}
								Thread.sleep(turntime);
								if (sliding == true) {
									goForward();
									Thread.sleep(distance);
									if (sliding == true) {
										stopGoing();
										sliding = false;
										speed = tempspeed;
									}
								}
							}
						}
					} catch (Exception e) {
						e.printStackTrace();
					}
				}
			}).start();
		}
	}

	@Override
	public void slidecancel() {
		if (sliding == true) {
			if (tempspeed != 999) {
				speed = tempspeed;
				sliding = false;
			}
		}
	}

	@Override
	public Integer clickSteer(String str) {
		tempstring = str;
		tempint = 999;
		String xy[] = tempstring.split(" ");
		if (Integer.parseInt(xy[1]) != 0) {
			tempint = clickCam(Integer.parseInt(xy[1]));
		}
		new Thread(new Runnable() {
			public void run() {
				try {
					String xy[] = tempstring.split(" ");
					if (Integer.parseInt(xy[0]) != 0) {
						if (Integer.parseInt(xy[1]) != 0) {
							Thread.sleep(camwait);
						}
						clickNudge(Integer.parseInt(xy[0]));
					}
				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
		return tempint;
	}

	@Override
	public void clickNudge(Integer x) {
		if (x > 0) {
			direction = "right";
		} else {
			direction = "left";
		}
		clicknudgedelay = maxclicknudgedelay * (Math.abs(x)) / 320;
		/*
		 * multiply clicknudgedelay by multiplier multiplier increases to
		 * CONST(eg 2) as x approaches 0, 1 as approaches 320
		 * ((320-Math.abs(x))/320)*1+1
		 */
		double mult = Math.pow(((320.0 - (Math.abs(x))) / 320.0), 3)
				* clicknudgemomentummult + 1.0;
		// System.out.println("clicknudgedelay-before: "+clicknudgedelay);
		clicknudgedelay = (int) (clicknudgedelay * mult);
		// System.out.println("n: "+clicknudgemomentummult+" mult: "+mult+" clicknudgedelay-after: "+clicknudgedelay);
		new Thread(new Runnable() {
			public void run() {
				try {
					tempspeed = speed;
					speed = speedfast;
					if (direction.equals("right")) {
						turnRight();
					} else {
						turnLeft();
					}
					Thread.sleep(clicknudgedelay);
					speed = tempspeed;
					if (movingforward == true) {
						goForward();
					} else {
						stopGoing();
					}
				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
	}

	@Override
	public Integer clickCam(Integer y) {
		Integer n = maxclickcam * y / 240;
		camservopos -= n;
		if (camservopos > camposmax) {
			camservopos = camposmax;
		}
		if (camservopos < camposmin) {
			camservopos = camposmin;
		}

		new Thread(new Runnable() {
			public void run() {
				try {
					byte[] command = { CAM, (byte) camservopos };
					sendCommand(command);
					checkForHoldServo();
				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
		return camservopos;
	}

	@Override
	public void releaseCameraServo() {
		new Thread(new Runnable() {
			public void run() {
				try {
					sendCommand(CAMRELEASE);
				} catch (Exception e) {
					e.printStackTrace();
				}
			}
		}).start();
	}

	public void checkForHoldServo() {

		if (application.stream == null) return;

		if (!holdservo || application.stream.equals("stop")
				|| application.stream.equals("mic")
				|| application.stream == null) {
			Util.delay(camwait);
			sendCommand(CAMRELEASE);
		}
	}

	@Override
	public void updateSteeringComp() {
		byte[] command = { COMP, (byte) steeringcomp };
		new Sender(command);
	}

}