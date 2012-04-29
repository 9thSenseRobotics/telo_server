/*
 * WebCamApplet.java
 *
 * Created on February 25, 2002, 8:47 AM
 */

import java.io.*;
import java.awt.*;
import java.applet.*;
import java.net.URL;
import java.net.MalformedURLException;
/**
 *
 * @author  mike morrison
 */
public class WebCamApplet extends Applet {
    private ImageDownloader downloader;
    private ImageCanvas ic;
    /** Creates a new instance of WebCamApplet */
    public WebCamApplet() {
        
    }
    public void init () {
        try{
            
            int width = Integer.parseInt(this.getParameter("width"));
            int height = Integer.parseInt(this.getParameter("height"));
	    int defFPS = Integer.parseInt(this.getParameter("FPS"));
            URL hostURL = new URL(getParameter("URL"));
            setLayout(new GridLayout());

            ic = new ImageCanvas(this,hostURL.getHost(),hostURL.getPort(),defFPS,width,height);
            add(ic);
        }catch(NumberFormatException e){
            e.printStackTrace();
        }catch(MalformedURLException e){
            e.printStackTrace();
        }
    }
    public void stop(){
        ic.disconnect();
        ic = null;
    }

}
