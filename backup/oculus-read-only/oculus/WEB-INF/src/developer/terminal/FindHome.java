package developer.terminal;

import java.io.*;
import java.net.*;

import developer.CommandServer;

import oculus.OptionalSettings;
import oculus.PlayerCommands;
import oculus.State;
import oculus.Util;

public class FindHome extends AbstractTerminal {

	long start = System.currentTimeMillis();
	boolean looking = true;
	
	public FindHome(String ip, String port, final String user, final String pass)
			throws NumberFormatException, UnknownHostException, IOException {
		
		super(ip, port, user, pass);

		connect();
		
		// if docked, run test... if not, just try getting home 
		if( undock()) { 
			
			// calibrate();
			
		}else{
			
			System.out.println(".. bot is not docked! lost in space...");
			
			// spinFind();
			
			out.println(PlayerCommands.autodock.toString() + " go");
			
		}
	
		// this should kill us ! 
		out.println("bye");
		Util.delay(5000);

		// testing only
		System.out.println("....  errrorrrr ... find home closed its self??");
		Util.delay(5000);
		shutdown();
	}

	/** 
	 * 
	 */
	public boolean undock() {
		if (state.get(State.dockstatus) != null) {
			if (state.get(State.dockstatus).equals(State.docked)) {

				System.out.println(".. docked, so undocking.");
				out.println("undock");
				
				out.println(PlayerCommands.autodock.toString() + " " + State.undock);
				
				Util.delay(3000);
				out.println("find");
				
				/*
				
				while (state.getBoolean(State.dockgrabbusy)) {
					
					//out.println("publish camera");
					System.out.println("... waiting on grab");
					Util.delay(2000);
					
				}
				
				System.out.println( "dock x : " + state.get(State.dockxpos));
				System.out.println( "dock y : " + state.get(State.dockypos));
				System.out.println( "size x : " + state.get(State.dockxsize));
				System.out.println( "size y : " + state.get(State.dockysize));
				System.out.println( "dense  : " + state.get(State.dockslope));
				
				int dockx = state.getInteger(State.dockxpos);
				int docky = state.getInteger(State.dockysize);
				int sizex = state.getInteger(State.dockxsize);
				int sizey = state.getInteger(State.dockysize);
				
				out.println("nudge backward");
				Util.delay(3000);
				out.println("find");
				Util.delay(3000);

				System.out.println( "second dock x : " + state.get(State.dockxpos));
				System.out.println( "second dock y : " + state.get(State.dockypos));
				System.out.println( "second size x : " + state.get(State.dockxsize));
				System.out.println( "second size y : " + state.get(State.dockysize));
				System.out.println( "second slope  : " + state.get(State.dockslope));
				
				int deltadockx = dockx - state.getInteger(State.dockxpos);
				int deltadocky = docky - state.getInteger(State.dockysize);
				int deltasizex = sizex - state.getInteger(State.dockxsize);
				int deltasizey = sizey - state.getInteger(State.dockysize);
		
				System.out.println("delta x : " + deltadockx);
				System.out.println("delta x : " + deltadocky);
				System.out.println("delta x : " + deltasizex);
				System.out.println("delta x : " + deltasizey);

				// turn round some
				
				int nudges = state.getInteger(OptionalSettings.offcenter.toString());
				int fullturn = state.getInteger(OptionalSettings.aboutface.toString()) * 2;
				System.out.println("... found offest: " + nudges + " fullturn: " + fullturn);

				// default if not 
				if(fullturn==State.ERROR) {	
					out.println("settings " + OptionalSettings.aboutface.toString() + " 3000");
					fullturn = 3000;
				}
				
				out.println("move left");
				Util.delay(fullturn); 
				out.println("stop");
				return true;
*/
				
			}
		}
		return false;
	}

	
	/** wait on the camera and battery, dock */
	public void connect() {

		out.println("settings");
		out.println("state");
		Util.delay(500);

		while (true) {
			if (state.get(State.batterystatus) == null) {
				out.println(PlayerCommands.battstats.toString());
				Util.delay(2000);
			} else break;
		}

		while (true) {
			if (state.get("publish") == null) {
				out.println("publish camera");
				Util.delay(2000);
			} else if (state.get("publish").equals("camera")) break;
		}

		// get grabber going
		out.println("find");
		while (state.getBoolean(State.dockgrabbusy)) {
			
			//out.println("publish camera");
			System.out.println("... waiting on grab");
			Util.delay(2000);
			
		}
		
		// clear old failures
		if (state.getBoolean(State.losttarget)) {
			System.out.println("..... recovering from old attempt! ");
			out.println("state " + State.losttarget + " " + false);
		}

		int nudges = state.getInteger(OptionalSettings.offcenter.toString());
		int aboutface = state.getInteger(OptionalSettings.aboutface.toString());
		System.out.println("...found offest: " + nudges + " aboutface: " + aboutface);

		out.println("state autodocking false");
		
		
		//	out.println("settings stopdelay 300");
		///out.println("settings volume 90");
		
		// out.println("state foo true");
		// out.println("image");
		// out.println("find");

		// Util.delay(5000);
	}
	
	/** */ 
	public void calibrate() {
		
		// don't calibrate on dead battery!
		if (state.getInteger(State.batterylife) < 50) {
			System.out.println("... this a bad move mitch, batttery = "
					+ state.get(State.batterylife));
			out.println("beep");
			out.println("beep");
			shutdown();
		}

		int nudges = spinFind();		
		out.println("settings " + OptionalSettings.offcenter.toString() + " " + nudges);

		// hand over control, wait for docking...
		out.println("dock");
		start = System.currentTimeMillis();
		while (true) {

			Util.delay(500);

			if (state.get(State.dockstatus) != null)
				if (state.get(State.dockstatus).equals(State.docked))
					break;
			
			if ((System.currentTimeMillis() - start) > State.TWO_MINUTES) {
				System.out.println("FindHome, Aborting, time out..");
				out.println("beep");
				shutdown();
			}

			// detect new failures failures
			if (state.getBoolean(State.losttarget)) {
				System.out.println("FindHome, Aborting, target lost.. ");				
				out.println("beep");
				shutdown();
				
			}
		}

		System.out.println("autodocking, took [" + ((System.currentTimeMillis() - start) / 1000) + "] sec");
	}

	/** spin until dock in view */
	public int spinFind() {
		
		out.println("settings nudgedelay 320");
		
		int i = 0;
		start = System.currentTimeMillis();
		while (looking) {

			System.out.println("[" + (System.currentTimeMillis() - start) + "] ms into spinFind().");
			
			if ((state.get(State.dockxpos) != null)) {
				if (state.getInteger(State.dockxpos) > 0) {

					System.out.println("... i see the dock, dock!");

					// three checks
					out.println("find");
					Util.delay(5000);
					if (state.getInteger(State.dockxpos) > 0) {

						out.println("nudge backward");
						Util.delay(5000);
						out.println("find");
						Util.delay(5000);

						// ok, dock lock must be good
						if (state.getInteger(State.dockxsize) > 0) looking = false;
					}
				}
			}

			if ((System.currentTimeMillis() - start) > State.FIVE_MINUTES) {
				System.out.println("FindHome(), looking is Aborting...");
				shutdown();
			}

			if (looking) {

				out.println("nudge left");
				Util.delay(3000);
				
				// get grabber going
				out.println("find");
				while (state.getBoolean(State.dockgrabbusy)) {
					
					//out.println("publish camera");
					System.out.println("... waiting on grab inside spin");
					Util.delay(2000);
					
				}
			}
		}
		
		System.out.println("spins: " + i);

		return i;
	}

	/** put settings and state values together */
	public void parseInput(final String str) {

		// System.out.println("_parse: " + str);

		if (str.indexOf(CommandServer.SEPERATOR) > 0) {

			String[] cmd = str.split(CommandServer.SEPERATOR);
			if (cmd.length == 2)
				state.set(cmd[0], cmd[1]);

		} else {
			
			System.out.println("_parse setting : " + str);


			String[] cmd = str.split(" ");
			if (cmd.length == 2)
				state.set(cmd[0], cmd[1]);
		}
	}

	/** parameters: ip, port, user name, password [commands] */
	public static void main(String args[]) throws IOException {
		new FindHome(args[0], args[1], args[2], args[3]);
	}
}