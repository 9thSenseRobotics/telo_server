var maxlogbuffer = 10000;
var stream = "stop";
var reloadinterval = 600000; // 10 min
var initialize = false;
var username = false;

function server_loaded() {
}

function initialize_loaded() {
	// window.open('server.html','_self'); // bypass init
	initialize = true;
}

function flashloaded() {
	if (!initialize) { setTimeout("reload();", reloadinterval); }
	setTimeout("callServer('checkforbattery','init')",2000);
	setTimeout("callServer('checkforbattery','ispresent')",7000);
	openxmlhttp("rtmpPortRequest",rtmpPortReturned);
}

function openxmlhttp(theurl, functionname) {
	  if (window.XMLHttpRequest) {// code for all new browsers
	    xmlhttp=new XMLHttpRequest();}
	  else if (window.ActiveXObject) {// code for IE5 and IE6
	    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP"); 
	    theurl += "?" + new Date().getTime();
	  }
	  if (xmlhttp!=null) {
	    xmlhttp.onreadystatechange=functionname; // event handler function call;
	    xmlhttp.open("GET",theurl,true);
	    xmlhttp.send(null);
	  }
	  else {
	    alert("Your browser does not support XMLHTTP.");
	  }
}

function rtmpPortReturned() { //xmlhttp event handler
	if (xmlhttp.readyState==4) {// 4 = "loaded"
		if (xmlhttp.status==200) {// 200 = OK
			getFlashMovie("oculus_grabber").setRtmpPort(xmlhttp.responseText);
			var mode = "server";
			if (initialize) { mode = "init"; }
			getFlashMovie("oculus_grabber").connect(mode);
		}
	}
}


function reload() {
	if (stream == "stop") {
		message("refreshing page", null);
		window.location.reload();
	}
	else { setTimeout("reload();", 10000); } //10 sec, keep checking until stream stopped by user
}

function message(message,status) {
	if (/^populatevalues/.test(message)) { populatevalues(message); message=""; }
	if (/^<CHAT>/.test(message)) {
		message = "<span style='font-size: 20px'>"+message.slice(6)+"</span>";
	}
//	if (message=="playerbroadcast") { videooverlay(parseInt(status)); message=""; status = null; }

	//messages not wanting to be displayed should erase content and go above here

	if (message != "") {
		var a = document.getElementById("messagebox");
		var str = a.innerHTML;
		if (str.length > maxlogbuffer) {
			str = str.slice(0, maxlogbuffer);
		}
		var datetime="";
		if (!initialize) {
			var d = new Date();
			var minutes = d.getMinutes();
			if (minutes < 10) { minutes = "0"+minutes; }
			var seconds = d.getSeconds();
			if (seconds < 10) { seconds = "0"+seconds; }
			datetime += "<span style='font-size: 11px; color: #666666;'>";
			datetime += d.getDate()+"-"+(d.getMonth()+1)+"-"+d.getFullYear();
			datetime += " "+d.getHours()+":"+minutes + ":"+seconds;
			datetime +="</span>";
		}
		a.innerHTML = "<table><tr valign='top'><td class='message'>&bull; </td><td class='message'>"+message+" " +
				datetime + "</td></td></table>" + str;
	}
	
	if (/^connected/.test(message)) { // some things work better if down here -wtf???
		init(); 
	}
	if (message=="launch server") { window.open('server.html','_self'); }
	if (/^streaming/.test(message)) { 
		var b = message.split(" ");
		stream = b[1]; 
	}
	if (message=="shutdown") { shutdownwindow(); } 
	if (message=="playing player stream") { screensize("full"); }
	if (message=="player stream stopped") { screensize("reduced"); }
	if (status != null && !initialize) { setstatus(status); } 
}

function setstatus(status) {
	var s=status.split(" ");
	var a;
	for (var n=0; n<s.length; n=n+2) {
		if (a= document.getElementById(s[n]+"_status")) {
			a.innerHTML = s[n+1].replace("&nbsp;"," ");
		}
	}
}

function getFlashMovie(movieName) {
	var isIE = navigator.appName.indexOf("Microsoft") != -1;
	return (isIE) ? window[movieName] : document[movieName];
}

function callServer(fn, str) {
	getFlashMovie("oculus_grabber").flashCallServer(fn,str);
} 

function saveandlaunch() {
	str = "";
	var s;
	var oktosend = true;
	var msg = "";
	//user password
	var user = document.getElementById("newusername").value;
	if (user != "") {
		var pass = document.getElementById("userpass").value;
		var passagain = document.getElementById("userpassagain").value;
		if (pass != passagain || pass=="") {
			oktosend = false;
			msg += "*error: passwords didn't match, try again "; 
		}
		if (/\s+/.test(user)) { 
			oktosend = false;
			msg += "*error: no spaces allowed in user name "; 
		} 
		if (/\s+/.test(pass)) { 
			oktosend = false;
			msg += "*error: no spaces allowed in password "; 
		}
		str += "user " + user + " password " + pass + " ";  
	}
	
	//battery
//	if (document.getElementById("battery").checked) { str += "battery yes "; }
//	else { str += "battery no "; }

	//httpport
	s = document.getElementById("httpport").value;
	if (s=="") { 
		message += "http port is blank ";
		oktosend = false; 
	}
	else { str += "httpport "+s+" "; }
	
	//rtmpport
	s = document.getElementById("rtmpport").value;
	if (s=="") { 
		message += "rtmp port is blank ";
		oktosend = false; 
	}
	else { str += "rtmpport "+s+" "; }	
	
	//skipsetup
	if (document.getElementById("skipsetup").checked) {str += "skipsetup yes "; }
	else { str += "skipsetup no "; }
	
	//TODO: Brad
	//if (document.getElementById("developer").checked) {str += "developer "; }
	//if (document.getElementById("holdservo").checked) {str += "holdservo "; }
	//if (document.getElementById("loginnotify").checked) {str += "loginnotify "; }
	//if (document.getElementById("sonarconnected").checked) {str += "sonarconnected "; }

	
	if (msg != "") { message(msg); }
	if (oktosend) {
		message("submitting info",null);
		callServer("saveandlaunch",str);
	}
}

function init() {
	if (initialize) {
		getFlashMovie("oculus_grabber").playlocal();
		callServer("populatesettings","");
	}
	else { callServer("autodock","getdocktarget"); } // sets variables
}

function populatevalues(values) {
	splitstr = values.split(" ");
	for (var n=1; n<splitstr.length; n=n+2) { // username battery comport httpport rtmpport
		if (splitstr[n] == "username") {  
			username = true;
			document.getElementById("username").innerHTML = "<b>"+splitstr[n+1]+"</b>"; 
		}
		if (splitstr[n]== "battery") {
			var a=document.getElementById("battery");
			var str = splitstr[n+1];
			if (str == "nil") { a.innerHTML="not found"; }
			else { a.innerHTML = "present"; }
		}
		if (splitstr[n]=="comport") {
			a = document.getElementById("comport");
			var str = splitstr[n+1];
			if (str == "nil") { a.innerHTML="not found <a href=\"http://www.xaxxon.com/shop\" target=\"_blank\">buy now</a>"; }
			else { a.innerHTML = "found on "+str; }
		}
		if (splitstr[n]=="lightport") {
			a = document.getElementById("lightport");
			var str = splitstr[n+1];
			if (str == "nil") { a.innerHTML="not found <a href=\"http://www.xaxxon.com/shop\" target=\"_blank\">buy now</a>"; }
			else {
				document.getElementById("lightdiv").style.display = "";
				a.innerHTML = "found on "+str; 
			}
		}
		if (splitstr[n]=="lanaddress") {
			a = document.getElementById("lanaddress");
			var str = splitstr[n+1];
			if (str == "nil") { a.innerHTML="not found"; }
			else { a.innerHTML = str; }
		}
		if (splitstr[n]=="wanaddress") {
			a = document.getElementById("wanaddress");
			var str = splitstr[n+1];
			if (str == "nil") { a.innerHTML="not found"; }
			else { a.innerHTML = str; }
		}
		if (splitstr[n]=="httpport") { document.getElementById("httpport").value = splitstr[n+1]; }
		if (splitstr[n]=="rtmpport") { document.getElementById("rtmpport").value = splitstr[n+1]; }
	}
	if (!username) { 
		document.getElementById("username").innerHTML = "<b>"+splitstr[n+1]+"</b>"; 
		userbox("show");
	}
}

function userbox(state) {
	if (state=="show") {
		document.getElementById('changeuserbox').style.display='';
		document.getElementById('changeuserboxlink').style.display='none';
	}
	else {
		document.getElementById('changeuserbox').style.display='none';
		document.getElementById('changeuserboxlink').style.display='';
	}
}

function ifnotshow() {
	document.getElementById('ifnot').style.display='';
}

function quit() {
	callServer('systemcall','red5-shutdown.bat');
	message("shutdown",null);
}

function restart() {
	message("shutdown",null);
	callServer('restart','')
}

function shutdownwindow() {
	window.open('about:blank','_self');
}

function chat() {
	var a = document.getElementById('chatbox_input');
	var str = a.value;
	a.value = "";
	if (str != "") {
		callServer("chat", "<i>OCULUS</i>: "+str);
	}
}

function keypress(e) {
	var keynum;
	if (window.event) {
		keynum = e.keyCode;
	}// IE
	else if (e.which) {
		keynum = e.which;
	} // Netscape/Firefox/Opera
	return keynum;
}

//function videooverlay(n) {
//	if (n == 0) {
//		document.getElementById("flashoverlay").style.display = "";
//	}
//	else { document.getElementById("flashoverlay").style.display = "none"; } 
//}


function factoryreset(){
	if(confirm("Restore factory default settings?\n(A backup file will be created and application restarted)")){
		callServer('factoryreset','');
	}
}

function screensize(mode) {
	var l = document.getElementById('leftsidebar')
	var r = document.getElementById('rightsidebar');
	var a = document.getElementById('videocontainer');
	var w;
	var h;
	if (mode == "full") {
		l.style.display = "none";
		l.style.width = "0px";
		r.style.display = "none";
		r.style.width = "0px";
		document.getElementById('reducevidlink').style.display = "";
		document.getElementById('enlargevidlink').style.display = "none";
		document.getElementById('topbar').style.height = "0px";
		document.getElementById('topbar').style.display = "none";
		h = document.getElementById("maintable").offsetHeight;
		h = Math.floor(h/3)*3-2;
		w = h*4/3;
		a.style.width = w +"px";
		a.style.height = h+"px";
	}
	else {
		l.style.display = "";
		l.style.width = "180px";
		r.style.display = "";
		r.style.width = "180px";
		w = 400;
		h = 300;
		a.style.width = w +"px";
		a.style.height = h+"px";
		document.getElementById('reducevidlink').style.display = "none";
		document.getElementById('enlargevidlink').style.display = "none"; // disabled
		document.getElementById('topbar').style.display = "";
		document.getElementById('topbar').style.height = "30px";
	}
	getFlashMovie("oculus_grabber").sizeChanged(w, h);
}
