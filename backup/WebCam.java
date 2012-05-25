/*
 * WebCam.java
 *
 * Created on February 24, 2002, 12:25 AM
 */

import java.io.*;
import java.awt.*;
import java.awt.event.*;
import java.applet.*;
/**
 *
 * @author  mike morrison
 */
public class WebCam extends Frame
{
  private Applet applet;
  private Frame f;
  private ImageCanvas imageCanvas;
  private TextField input;
  public WebCam (Applet a, String host, int port)
  {
    applet = a;
    f = this;
    imageCanvas = new ImageCanvas (null, host, port, 30, 320, 240);
    imageCanvas.setMainWindow(this);
    setTitle ("Webcam viewer: " + host + ":" + port);

    f.add (imageCanvas);
    f.pack ();
    f.show ();
    addComponentListener (new ComponentListener ()
			  {
			  public void componentHidden (ComponentEvent e)
			  {
			  }
			  public void componentMoved (ComponentEvent e)
			  {
			  }
			  public void componentResized (ComponentEvent e)
			  {
			  imageCanvas.setImageSize (e.getComponent ().
						    getSize ());}
			  public void componentShown (ComponentEvent e)
			  {
			  }
			  }
    );
    addWindowListener (new WindowListener ()
		       {

		       public void windowActivated (WindowEvent e)
		       {
		       }
		       public void windowClosed (WindowEvent e)
		       {

		       }
		       public void windowClosing (WindowEvent e)
		       {
		       System.exit (0);}
		       public void windowDeactivated (WindowEvent e)
		       {
		       }
		       public void windowDeiconified (WindowEvent e)
		       {
		       }
		       public void windowIconified (WindowEvent e)
		       {
		       }
		       public void windowOpened (WindowEvent e)
		       {
		       }
		       }
    );
  }

  public static void main (String args[])
  {
    new WebCam (null, args[0], Integer.parseInt (args[1]));
  }

}
