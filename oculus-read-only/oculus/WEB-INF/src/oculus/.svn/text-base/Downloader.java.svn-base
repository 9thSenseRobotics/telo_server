package oculus;

import java.io.*;
import java.net.*;

public class Downloader {
	
	/**
	 * 
	 * Down load a given URL to the local disk. Will delete existing file first, and create directory if required. 
	 *
	 * @param fileAddress is the full http://url of the remote file
	 * @param localFileName the file name to use on the host
	 * @param destinationDir the folder name to put this down load into 
	 * @return true if the file is down loaded, false on any error. 
	 * 
	 */
	public boolean FileDownload(final String fileAddress,
			final String localFileName, final String destinationDir) {

		long start = System.currentTimeMillis();
		
		InputStream is = null;
		OutputStream os = null;
		URLConnection URLConn = null;

		// create path to local file
		final String path = destinationDir + System.getProperty("file.separator") + localFileName;

		// create target directory
		new File(destinationDir).mkdirs();

		// delete target first
		new File(path).delete();

		// test is really gone
		if (new File(path).exists()) {
			System.out.println("OCULUS: can't delete existing file: " + path);
			return false;
		}

		try {

			int ByteRead, ByteWritten = 0;
			os = new BufferedOutputStream(new FileOutputStream(path));

			URLConn = new URL(fileAddress).openConnection();
			is = URLConn.getInputStream();
			byte[] buf = new byte[1024];

			// pull in the bytes
			while ((ByteRead = is.read(buf)) != -1) {
				os.write(buf, 0, ByteRead);
				ByteWritten += ByteRead;
			}

			System.out.println("OCULUS: saved to local file: " + path + " bytes: " + ByteWritten);
			System.out.println("download took: "+ (System.currentTimeMillis()-start) + " ms");
			System.out.println("downloaded " + ByteWritten + " bytes to: " + path);

		} catch (Exception e) {
			System.out.println("OCULUS: " + e.getMessage());
			return false;
		} finally {
			try {
				is.close();
				os.close();
			} catch (IOException e) {
				System.out.println("OCULUS: " + e.getMessage());
				return false;
			}
		}

		// all good
		return true;
	}


	/**
	 * @param zipFile the zip file that needs to be unzipped
	 * @param destFolder the folder into which unzip the zip file and create the folder structure
	 */
	public boolean unzipFolder( String zipFile, String destFolder ) {
		
		final String zip = (System.getenv("RED5_HOME") + "\\" + zipFile).trim();
		final String des = (System.getenv("RED5_HOME") + "\\" + destFolder).trim(); 
		
		// clean target 
		if(new File(des).exists()) deleteDir(new File(des));
		
		// 
		if( ! new File(zip).exists()){	
			System.out.println("no zip file found: " + zip);
			return false;
		}
				
		// if zip exists, blocking extraction 
		Util.systemCallBlocking("fbzip -e -p " + zip + " " + des);
			
		// test if folders 
		if(new File(des).exists())
			if(new File(des).isDirectory())
				if(new File(des+"\\update").exists())
					if(new File(des+"\\update").isDirectory()) 
						return true;
		
		// error state
		return false;
	}
	
	/**
	 * @param zipFile the zip file that needs to be unzipped
	 * @param destFolder the folder into which unzip the zip file and create the folder structure
	 
	public boolean unzipFolderJava( String zipFile, String destFolder ) {
		boolean result = false;
		try {
			ZipFile zf = new ZipFile(zipFile);
			Enumeration< ? extends ZipEntry> zipEnum = zf.entries();
			String dir = destFolder;

			while( zipEnum.hasMoreElements() ) {
				ZipEntry item = (ZipEntry) zipEnum.nextElement();

				if (item.isDirectory()) {
					File newdir = new File(dir + File.separator + item.getName());
					newdir.mkdir();
				} else {
					String newfilePath = dir + File.separator + item.getName();
					File newFile = new File(newfilePath);
					if (!newFile.getParentFile().exists()) {
						newFile.getParentFile().mkdirs();
					}

					InputStream is = zf.getInputStream(item);
					FileOutputStream fos = new FileOutputStream(newfilePath);
					int ch;
					while( (ch = is.read()) != -1 ) {
						fos.write(ch);
					}
					is.close();
					fos.close();
				}
			}
			result = true;
			zf.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
		return result;
	}
	 */
	
	/**
	 * @param filename
	 */
	public void deleteFile(String filename) {
		File f = new File(filename);
		try {
			f.delete();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
	
	public boolean deleteDir(File dir) {
	    if (dir.isDirectory()) {
	        String[] children = dir.list();
	        for (int i=0; i<children.length; i++) {
	            boolean success = deleteDir(new File(dir, children[i]));
	            if (!success) return false;
	        }
	    }

	    // The directory is now empty so delete it
	    return dir.delete();
	}

}
