var ctroffset = 0; //295
var connected = false;
var logintimeout = 10000; 
var logintimer; // timer
var username;
var streammode = "stop";
var steeringmode;

function loaded() {
    overlay("on");
	resized();
}

function resized() {
	docklineposition();
	overlay("");
}

function flashloaded() {
	overlay("on");
	if (/auth=/.test(document.cookie)) { loginfromcookie(); }
	else { login(); }
}

function callServer(fn, str) {
	getFlashMovie("oculus_player").flashCallServer(fn,str);
}

function play(str) {
	streammode = str;
	var num = 1;
	if (streammode == "stop") { num =0 ; } 
	getFlashMovie("oculus_player").flashplay(num);
}

function getFlashMovie(movieName) {
	var isIE = navigator.appName.indexOf("Microsoft") != -1;
	return (isIE) ? window[movieName] : document[movieName];
}

function publish(str) {
	callServer("publish", str);
}

function message(message, colour, status, value) {
	if (status != null) {  //(!/\S/.test(d.value))
		if (status == "multiple") { setstatusmultiple(value); }
		else { setstatus(status, value); }
	}
}

function setstatus(status, value) {
	if (status=="vidctroffset") { ctroffset = parseInt(value); }
	//if (status=="motion" && value=="disabled") { motionenabled = false; }
	if (value.toUpperCase() == "CONNECTED" && !connected) { // initialize
		overlay("off");
		// countdowntostatuscheck(); 
		connected = true;
		// setTimeout("videomouseaction = true;",30); // firefox screen glitch fix
		clearTimeout(logintimer);
	}
	if (status.toUpperCase() == "STORECOOKIE") {
		createCookie("auth",value,30); 
	}
	if (status == "someonealreadydriving") { someonealreadydriving(value); }
	if (status == "user") { username = value; }
	if (status == "hijacked") { window.location.reload(); }
	if (status == "stream" && (value.toUpperCase() != streammode.toUpperCase())) { play(value); }
	if (status == "address") { document.title = "Cyclops "+value; }
}

function setstatusmultiple(value) {
	var statusarray = new Array();
	statusarray = value.split(" ");
	for (var i = 0; i<statusarray.length; i+=2) {
		setstatus(statusarray[i], statusarray[i+1]);
	}
}

function motionenabletoggle() {
	callServer("motionenabletoggle", "");
}

function move(str) {
	callServer("move", str);
}

function nudge(direction) {
	callServer("nudge", direction);
}

function slide(direction) {
	callServer("slide", direction);
}

function toggledockline() {
	var a = document.getElementById("dockline");
	if (a.style.display == "") { a.style.display = "none"; }
	else { a.style.display = ""; }
	docklineposition();
}

function docklineposition() {
	var a = document.getElementById("dockline");
	var b = document.getElementById("video");
	a.style.left = b.offsetLeft + b.offsetWidth/2+(ctroffset/2);
	a.style.top = b.offsetTop;
	a.style.height = b.offsetHeight;
}

function speech() {
	var a = document.getElementById('speechbox');
	var str = a.value;
	a.value = "";
	callServer("speech", str);
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

function camera(fn) {
	callServer("cameracommand", fn);
}

function speedset(str) {
	callServer("speedset", str);
}

function dock(str) {
	callServer("dock", str);
}


function videooverlayposition() {
	var a = document.getElementById("videooverlay");
    var video = document.getElementById("video");
    a.style.width = video.offsetWidth;
    a.style.height = video.offsetHeight;
    a.style.left = video.offsetLeft;
    a.style.top = video.offsetTop;
}

function overlay(str) {
	var a=document.getElementById("overlay");
	var b = document.getElementById("pagecontainer");
	if (str=="on") { a.style.display = ""; }
	if (str=="off") { a.style.display = "none"; }
	//a.style.left = "5";
	//a.style.top = "5";
	//a.style.width = b.offsetWidth-10;
	//a.style.height = b.offsetHeight-10;
}

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

function loginfromcookie() {
	var str = ""; 
	str = readCookie("auth");
	getFlashMovie("oculus_player").connect(str);
	logintimer = setTimeout("eraseCookie('auth'); window.location.reload()", logintimeout);
}

function login() {
	document.getElementById("overlaydefault").style.display = "none";
	document.getElementById("login").style.display = "";
	document.getElementById("user").focus();	
}

function loginsend() {
	document.getElementById("overlaydefault").style.display = "";
	document.getElementById("login").style.display = "none";
	var str1= document.getElementById("user").value;
	var str2= document.getElementById("pass").value;
	var str3= document.getElementById("user_remember").checked;
	if (str3 == true) { str3="remember"; }
	else { eraseCookie("auth"); }
	getFlashMovie("oculus_player").connect(str1+" "+str2+" "+str3+" ");
	logintimer = setTimeout("window.location.reload()", logintimeout);
}

function logout() {
	eraseCookie("auth");
	window.location.reload();
}

function someonealreadydriving(value) {
	clearTimeout(logintimer);
	overlay("on");
	document.getElementById("overlaydefault").style.display = "none";
	document.getElementById("someonealreadydrivingbox").style.display = "";
	document.getElementById("usernamealreadydrivingbox").innerHTML = value.toUpperCase();
}

function beapassenger() {
	callServer("beapassenger", username);
	overlay("off");
	setstatus("connection","PASSENGER");
}

function assumecontrol() {
	callServer("assumecontrol", username);
}

function playerexit() {
	callServer("playerexit","");
}

function steeringmousedown(id) {
	document.getElementById(id).style.backgroundColor = "#45F239";
	setTimeout("document.getElementById('"+id+"').style.backgroundColor='transparent';",200);
	/*
	if (steeringmode == id) {
		move("stop");
		steeringmode="stop";
		id = null;
	}
	*/
	if (id == "forward") { move("forward"); }
	if (id == "backward") { move("backward"); }
	if (id == "rotate right") { move("right"); }
	if (id == "rotate left") { move("left"); }
	if (id == "slide right") { slide("right"); }
	if (id == "slide left") { slide("left"); }
	if (id == "nudge right") { nudge("right"); id = null; }
	if (id == "nudge left") { nudge("left"); id = null; }
	if (id == "nudge forward") { nudge("forward"); }
	if (id == "nudge backward") { nudge("backward"); }
	if (id == "stop") { move("stop"); }
	if (id == "camera up") { camera("upabit"); id=null; }
	if (id == "camera down") { camera("downabit"); id=null; }
	if (id == "camera horizontal") { camera("horiz"); id=null; }
	if (id == "speed slow") { speedset("slow"); id=null; }
	if (id == "speed medium") { speedset("med"); id=null; }
	if (id == "speed fast") { speedset("fast"); id=null; }
	if (id == "menu") { move("stop"); menu(); id=null; }
	if (id) {
		steeringmode = id;
	}
}

function menu() {
	overlay("on");
	document.getElementById("overlaydefault").style.display="none";   
	document.getElementById("login").style.display="none";
	document.getElementById("someonealreadydrivingbox").style.display="none";
	document.getElementById("menubox").style.display="";
	
}
