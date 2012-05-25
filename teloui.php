<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>9th Sense</title>
<script type="text/javascript">

// An AJAX interface to telorun.php which sends the actual data to Google/the robot
function sendCommand(commandData)
{
    var xmlhttp;
    if (window.XMLHttpRequest)
    {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp=new XMLHttpRequest();
    } else {
        // code for IE6, IE5
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    
    xmlhttp.onreadystatechange=function()
    {
        if (xmlhttp.readyState==4 && xmlhttp.status==200)
        {
            // uncomment next line to print the output of the AJAX result from telorun.php
            // document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
        }
    }
    // call to telorun.php, last argument is "true" to be asynchronous
    // note that here we drop in the phoneid into the URL to put it in the next page's $_GET.
    // also here is where we put the payload into the URL GET string
    xmlhttp.open("GET","telorun.php?phoneid=<?php echo $_GET["phoneid"]; ?>&payload=" + commandData,true);
    xmlhttp.send();
}
</script>
</head>
<body>

<!-- main ui -->
<div id="myDiv"><h2>Command the robot!</h2></div>
<button type="button" onclick="sendCommand('f')">Forward</button>
<button type="button" onclick="sendCommand('b')">Backward</button>
<button type="button" onclick="sendCommand('l')">Left</button>
<button type="button" onclick="sendCommand('r')">Right</button>
<!-- <button type="button" onclick="sendCommand('test string')">Right</button> -->


</body>
</html>

