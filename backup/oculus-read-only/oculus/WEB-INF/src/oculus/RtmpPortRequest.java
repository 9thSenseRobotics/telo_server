package oculus;

import java.io.IOException;
import java.io.PrintWriter;

import javax.servlet.*;
import javax.servlet.http.*;

public class RtmpPortRequest extends HttpServlet {
	private Settings settings;
	private static Application app = null;

	//TODO: need this??
	public static void setApp(Application a) {
		if(app != null) return;
		app = a;
	}
	
	public void doGet(HttpServletRequest request, HttpServletResponse response)
			throws ServletException, IOException {
		response.setContentType("text/html");
		PrintWriter out = response.getWriter();
		settings = new Settings();
		out.print(settings.readRed5Setting("rtmp.port"));
		out.close();
//		System.out.println("xmlhttphandler");
	}
}
