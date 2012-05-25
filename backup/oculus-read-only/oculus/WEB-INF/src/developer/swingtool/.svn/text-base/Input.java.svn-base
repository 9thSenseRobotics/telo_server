package developer.swingtool;

import java.io.*;
import java.net.*;

import javax.swing.*;

import oculus.PlayerCommands;

import java.awt.event.*;

public class Input extends JTextField implements KeyListener {

	private static final long serialVersionUID = 1L;
	private Socket socket = null;
	private PrintWriter out = null;
	private String userInput = null;
	private int ptr = 0;

	public Input(Socket s, final String usr, final String pass) {
		super();
		socket = s;

		try {
			out = new PrintWriter(new BufferedWriter(new OutputStreamWriter(socket.getOutputStream())), true);
		} catch (Exception e) {
			System.exit(-1);
		}

		
		// if connected, login now
		out.println(usr + ":" + pass);
		
		// get up to date 
		// out.println("state");
		
		// listen for key input 
		addKeyListener(this);
		// requestFocus();

	}
	
	// Manager user input
	public void send() {
		try {

			// get keyboard input
			userInput = getText().trim();
			
			// log to console
			// System.out.println("user typed :" + userInput);
			
			// send the user input to the server if is valid
			if (userInput.length() > 0) out.println(userInput);
			
			if (out.checkError()) System.exit(-1);
			
			if (userInput.equalsIgnoreCase("quit")) System.exit(-1);

			if (userInput.equalsIgnoreCase("bye")) System.exit(-1);

		} catch (Exception e) {
			System.exit(-1);
		}
	}

	@Override
	public void keyTyped(KeyEvent e) {
		final char c = e.getKeyChar();
		if (c == '\n' || c == '\r') {
			final String input = getText().trim();
			if (input.length() > 2) {

				send();
				
				// clear input screen 
				setText("");
			}
		} 
	}

	@Override
	public void keyPressed(KeyEvent e) {		
		
		PlayerCommands[] cmds = PlayerCommands.values();

		if(e.getKeyCode() == KeyEvent.VK_UP){
				
			if(ptr++ >= cmds.length) ptr = 0;
			
			setText(cmds[ptr].toString() + " ");
			
			setCaretPosition(getText().length());

		} else if(e.getKeyCode() == KeyEvent.VK_DOWN){
				
			if(ptr-- <= 0) ptr = cmds.length;
			
			setText(cmds[ptr].toString() + " ");
			
			setCaretPosition(getText().length());
			
		} else if(e.getKeyCode() == KeyEvent.VK_RIGHT) {
			
			String text = getText().trim();
			String[] list = text.split(" ");
			
			if(list[0].equals(PlayerCommands.move.toString())){
				
				if(list[1].equals("backward")) setText(list[0] + " forward");
				if(list[1].equals("left")) setText(list[0] + " right");
				if(list[1].equals("forward")) setText(list[0] + " backward");
				if(list[1].equals("right")) setText(list[0] + " left");

				setCaretPosition(getText().length() + 2);
				
				
			}
		} 
	}

	@Override
	public void keyReleased(KeyEvent e) {
		
		// System.out.println("............ " + e.toString());

	}
}
