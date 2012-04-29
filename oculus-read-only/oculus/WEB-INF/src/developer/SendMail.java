package developer;

import java.util.*;

import javax.activation.DataHandler;
import javax.activation.DataSource;
import javax.activation.FileDataSource;
import javax.mail.*;
import javax.mail.internet.*;

import oculus.Application;
import oculus.OptionalSettings;
import oculus.Settings;

/**
 * Send yourself an email from your gmail account
 * 
 * @author brad.zdanivsk@gmail.com
 */
public class SendMail {

	// TODO: take this from properties on startup if we want any smtp server
	private static int SMTP_HOST_PORT = 587;
	private static final String SMTP_HOST_NAME = "smtp.gmail.com";

	private Settings settings = new Settings();;
	private final String user = settings.readSetting(OptionalSettings.emailaddress.toString()); 
	private final String pass = settings.readSetting(OptionalSettings.emailpassword.toString()); 

	private String subject = null;
	private String body = null;
	private String fileName = null;
	
	// if set, send error messages to user's screen 
	private Application application = null;

	/** */
	public SendMail(final String sub, final String text, final String file) {
		
		subject = sub;
		body = text;
		fileName = file;

		new Thread(new Runnable() {
			public void run() {
				sendAttachment();
			}
		}).start();
	}

	/**	*/
	public SendMail(final String sub, final String text) {

		subject = sub;
		body = text;

		new Thread(new Runnable() {
			public void run() {
				sendMessage();
			}
		}).start();
	}


	/** send messages to user */
	public SendMail(final String sub, final String text, final String file, Application app) {
		
		subject = sub;
		body = text;
		fileName = file;
		application = app;
		
		new Thread(new Runnable() {
			public void run() {
				sendAttachment();
			}
		}).start();
	}

	/** send messages to user */
	public SendMail(final String sub, final String text, Application app) {
		
	//	settings = new Settings();

		subject = sub;
		body = text;
		application = app;
		
		new Thread(new Runnable() {
			public void run() {
				sendMessage();
			}
		}).start();
	}
	
	/** */
	private void sendMessage() {

		if (user == null || pass == null) {
			System.out.println("no email and password found in settings");
			return;
		}
		
		try {

			Properties props = new Properties();
			props.put("mail.smtps.host", SMTP_HOST_NAME);
			props.put("mail.smtps.auth", "true");
			props.put("mail.smtp.starttls.enable", "true");

			Session mailSession = Session.getDefaultInstance(props);
			Transport transport = mailSession.getTransport("smtp");

			// if (debug) mailSession.setDebug(true);

			MimeMessage message = new MimeMessage(mailSession);
			message.setSubject(subject);
			message.setContent(body, "text/plain");
			message.addRecipient(Message.RecipientType.TO, new InternetAddress(user));

			transport.connect(SMTP_HOST_NAME, SMTP_HOST_PORT, user, pass);
			transport.sendMessage(message, message.getRecipients(Message.RecipientType.TO));
			transport.close();

			// if (debug) System.out.println("... email sent");
			
			if(application!=null) application.message("email has been sent", null, null);

		} catch (Exception e) {
			//log.error(e.getMessage() + "error sending email, check settings");
			if(application!=null) application.message("error sending email", null, null);
		}
	}

	
	
	/** */
	private void sendAttachment() {

		if (user == null || pass == null) {
			// log.error("no email and password found in settings");
			System.out.println("OCULUS: no email and password found in settings");
			return;
		}
		
		try {

			// if (debug) System.out.println("sending email..");
			
			Properties props = new Properties();
			props.put("mail.smtps.host", SMTP_HOST_NAME);
			props.put("mail.smtps.auth", "true");
			props.put("mail.smtp.starttls.enable", "true");

			Session mailSession = Session.getDefaultInstance(props);
			Transport transport = mailSession.getTransport("smtp");

			// if (debug) mailSession.setDebug(true);

			MimeMessage message = new MimeMessage(mailSession);
			message.setSubject(subject);
			message.addRecipient(Message.RecipientType.TO, new InternetAddress(user));

			BodyPart messageBodyPart = new MimeBodyPart();
			messageBodyPart.setText(body);
			Multipart multipart = new MimeMultipart();
			multipart.addBodyPart(messageBodyPart);

			messageBodyPart = new MimeBodyPart();
			DataSource source = new FileDataSource(fileName);
			messageBodyPart.setDataHandler(new DataHandler(source));
			messageBodyPart.setFileName(fileName);
			multipart.addBodyPart(messageBodyPart);
			message.setContent(multipart);

			transport.connect(SMTP_HOST_NAME, SMTP_HOST_PORT, user, pass);
			transport.sendMessage(message, message.getRecipients(Message.RecipientType.TO));
			transport.close();

			//if (debug) System.out.println("... email sent");
			
			if(application!=null) application.message("email has been sent", null, null);

		} catch (Exception e) {
			// log.error(e.getMessage());
			System.out.println("error sending email, check settings");
			if(application!=null) application.message("error sending email", null, null);
		}
	}
}