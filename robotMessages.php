<?php
/*
	robotMessages.php - specifies PHP classes for the XML exchanged between robots and 
						the controllers over the XMPP/jabberd2 interface

         Copyright (c) 2012, 9th Sense, Inc.
         All rights reserved.

     This program is free software: you can redistribute it and/or modify
     it under the terms of the GNU General Public License as published by
     the Free Software Foundation, either version 3 of the License, or
     (at your option) any later version.

     This program is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     You should have received a copy of the GNU General Public License
     along with this program.  If not, see <http://www.gnu.org/licenses/>.

	 ----------------------------------------------------------------------------------
	 Once instantiated, you can reference the contents by $theObject->driverAddr, 
	 $theObject->robotAddr, etc.
	 
	 To output the contents as XML for sending, use $theObject->XML->asXML();
	 
	 See child classes for comments on constructors 
	 ----------------------------------------------------------------------------------
	 
	 Here are the XML formats for messages to and from robot. 
	 
	<!-- messageToRobot -->
	<?xml version="1.0" standalone="yes"?>
	<m> 
		<t>timeStamp</t> 
		<d>driverAddr</d> 
		<dn>driverName</dn>
		<r>robotAddr</r> 
		<c>commandChar</c>
		<a>commandArguments</a>
		<co>comment</co>
	</m>
	
	<!-- messageFromRobot -->
	<?xml version="1.0" standalone="yes"?>
	<m> 
		<t>timeStamp</t> 
		<d>driverAddr</d> 
		<r>robotAddr</r> 
		<re>responseValue</re>
		<co>comment</co>
	</m>
	 	
*/

function microtime_long()
{
	$time = microtime(true);
	return preg_replace('/\./', '', $time);
}

class robotMessages 
// Base class for all robot messages. Specifies the properties contained within the XML as well
// as $this->XML, which is a SimpleXML element that can be interacted with normally.
//
// See PHP's documentation for more info on the SimpleXML library:
//		http://php.net/manual/en/book.simplexml.php
//
// Also provides the default constructor to count arguments because because PHP doesn't 
// support method overloading, and sets the microtime() timestamp of its instantiation 
{
	// for messages to robot from driver
	public $driverAddr;
	public $driverName;
	public $robotAddr;
	public $commandChar;
	public $commandArguments;
	public $comment;

	// for messages from robot to driver
	public $responseValue;
	
	public $XML; // simpleXML type
	
	public $timeStamp; //when it was created  - time.microtime() (float)
	
	function __construct() 
    { 
    	$this->timeStamp = microtime_long(); // get timestamp at instantiation
    	
    	// default constructor because PHP doesn't support overloading
        $a = func_get_args(); 
        $i = func_num_args(); 
        if (method_exists($this,$f='__construct'.$i)) { 
            call_user_func_array(array($this,$f),$a); 
        } 
    } 
}

class messageToRobot extends robotMessages
// For messages that are sent from a driver or controller to the robot
//
// Usage:
// 
//	$mtr = new messageToRobot($rawXMLString); // build from the raw XML
//	$mtr = new messageToRobot($driverAddr, $driverName, $robotAddr, $commandChar [[,$commandArguments] [,$comment]]);
{	
	function __construct1($xmlStr) 
	// single-argument constructor: Build this class from its XML string
	{
		// parse XML string to create simpleXML element 
		$this->XML = new simpleXMLElement($xmlStr); 
		$this->XML->addChild('t', $this->timeStamp);
		
		// set the class' properties from the parsed XML element (if there's no corresponding XML element, they're just empty)
		$this->driverAddr = (string)$this->XML->d;
		$this->driverName = (string)$this->XML->dn;
		$this->robotAddr = (string)$this->XML->r;
		$this->commandChar = (string)$this->XML->c;
		$this->commandArguments = (string)$this->XML->a;
		$this->comment = (string) $this->XML->co;
	}

	function __construct4($driverAddr, $driverName, $robotAddr, $commandChar)
	// 4-argument constructor: build this class from its component data with a command but without an argument or comment
	{
		// set the class' properties from the passed-in values; if they're unspecified, set as empty
		$this->driverAddr = $driverAddr;
		$this->driverName = $driverName;
		$this->robotAddr = $robotAddr;
		$this->commandChar = $commandChar;
		$this->commandArguments = '';
		$this->comment = '';
		
		// now build the $this->XML property from what you've already set
		$this->XML = new SimpleXMLElement("<m></m>");
		$this->XML->addChild('t', $this->timeStamp);
		$this->XML->addChild('d', $this->driverAddr);
		$this->XML->addChild('dn', $this->driverName);
		$this->XML->addChild('r', $this->robotAddr);
		$this->XML->addChild('c', $this->commandChar);
	}
	function __construct5($driverAddr, $driverName, $robotAddr, $commandChar, $commandArguments)
	// 5-argument constructor: build this class from its component data with a command but without a comment
	{
		// set the class' properties from the passed-in values; if they're unspecified, set as empty
		$this->driverAddr = $driverAddr;
		$this->driverName = $driverName;
		$this->robotAddr = $robotAddr;
		$this->commandChar = $commandChar;
		$this->commandArguments = $commandArguments;
		$this->comment = '';
		
		// now build the $this->XML property from what you've already set
		$this->XML = new SimpleXMLElement("<m></m>");
		$this->XML->addChild('t', $this->timeStamp);
		$this->XML->addChild('d', $this->driverAddr);
		$this->XML->addChild('dn', $this->driverName);
		$this->XML->addChild('r', $this->robotAddr);
		$this->XML->addChild('c', $this->commandChar);
		$this->XML->addChild('a', $this->commandArguments);		
	}
	function __construct6($driverAddr, $driverName, $robotAddr, $commandChar, $commandArguments, $comment)
	// 6-argument constructor: build this class from its component data with a command and a comment
	{
		// set the class' properties from the passed-in values
		$this->driverAddr = $driverAddr;
		$this->driverName = $driverName;
		$this->robotAddr = $robotAddr;
		$this->commandChar = $commandChar;
		$this->commandArguments = $commandArguments;
		$this->comment = $comment;
		
		// now build the $this->XML property from what you've already set
		$this->XML = new SimpleXMLElement("<m></m>");
		$this->XML->addChild('t', $this->timeStamp);
		$this->XML->addChild('d', $this->driverAddr);
		$this->XML->addChild('dn', $this->driverName);
		$this->XML->addChild('r', $this->robotAddr);
		$this->XML->addChild('c', $this->commandChar);
		$this->XML->addChild('a', $this->commandArguments);
		$this->XML->addchild('co', $this->comment);
	}
	function __construct7($driverAddr, $driverName, $robotAddr, $commandChar, $commandArguments, $comment, $timeStamp)
	// 7-argument constructor: build this class from its component data with a command and a comment, plus timestamp
	{
		// set the class' properties from the passed-in values
		$this->driverAddr = $driverAddr;
		$this->driverName = $driverName;
		$this->robotAddr = $robotAddr;
		$this->commandChar = $commandChar;
		$this->commandArguments = $commandArguments;
		$this->comment = $comment;
		$this->timeStamp = $timeStamp;
		
		// now build the $this->XML property from what you've already set
		$this->XML = new SimpleXMLElement("<m></m>");
		$this->XML->addChild('t', $this->timeStamp);
		$this->XML->addChild('d', $this->driverAddr);
		$this->XML->addChild('dn', $this->driverName);
		$this->XML->addChild('r', $this->robotAddr);
		$this->XML->addChild('c', $this->commandChar);
		$this->XML->addChild('a', $this->commandArguments);
		$this->XML->addchild('co', $this->comment);
	}
}

class messageFromRobot extends robotMessages
// For messages that are sent from a driver or controller to the robot
//
// Usage:
// 
//	$mfr = new messageFromRobot($rawXMLString); // build from the raw XML
//	$mfr = new messageToRobot($driverAddr, $robotAddr, $responseValue [, $comment]);
{	
	function __construct1($xmlStr) 
	// single-argument constructor: Build this class from its XML string
	{
		// parse XML string to create simpleXML element 
		$this->XML = new simpleXMLElement($xmlStr);
		$this->XML->addChild('t', $this->timeStamp);
		
		// set the class' properties from the parsed XML element (if there's no corresponding XML element, they're just empty)
		$this->driverAddr = (string)$this->XML->d;
		$this->robotAddr = (string)$this->XML->r;
		$this->responseValue = (string)$this->XML->re;
		$this->comment = (string) $this->XML->co;
	}

	function __construct3($driverAddr, $robotAddr, $responseValue)
	// 3-argument constructor: build this class from its component data with a response but without a comment
	{
		// set the class' properties from the passed-in values; if they're unspecified, set as empty
		$this->driverAddr = $driverAddr;
		$this->robotAddr = $robotAddr;
		$this->responseValue = $responseValue;
		$this->comment = '';
		
		// now build the $this->XML property from what you've already set
		$this->XML = new SimpleXMLElement("<m></m>");
		$this->XML->addChild('t', $this->timeStamp);
		$this->XML->addChild('d', $this->driverAddr);
		$this->XML->addChild('r', $this->robotAddr);
		$this->XML->addChild('re', $this->responseValue);
	}
	function __construct4($driverAddr, $robotAddr, $responseValue, $comment)
	// 4-argument constructor: build this class from its component data with a response and a comment
	{
		// set the class' properties from the passed-in values
		$this->driverAddr = $driverAddr;
		$this->robotAddr = $robotAddr;
		$this->responseValue = $responseValue;
		$this->comment = $comment;
		
		// now build the $this->XML property from what you've already set
		$this->XML = new SimpleXMLElement("<m></m>");
		$this->XML->addChild('t', $this->timeStamp);
		$this->XML->addChild('d', $this->driverAddr);
		$this->XML->addChild('r', $this->robotAddr);
		$this->XML->addChild('re', $this->responseValue);
		$this->XML->addchild('co', $this->comment);
	}
}

function robotMessagesTestSuite ()
// for testing the functionality of robotMessagesTestSuite
{

	print "\n ------------------------------------------------------- \n";
	print "\n              messageFromRobot test suite\n";
	print "\n ------------------------------------------------------- \n\n";
	
	$messageFromRobot = new messageFromRobot ('driver@9thsense.com','robot@9thsense.com',
										'I received the command');
	print "object to XML without comment: \n";
	echo $messageFromRobot->XML->asXML();
	
	
	$messageFromRobot = new messageFromRobot ('driver@9thsense.com','robot@9thsense.com',
										'I received the command','This is a comment about my response');
	print "\n ------------------------------------------------------- \n";
	print "object to XML with argument with comment: \n";
	echo $messageFromRobot->XML->asXML();
	
	
	$messageFromRobot = new messageFromRobot ("<?xml version='1.0' standalone='yes'?><m><d>driverAddr</d><r>robotAddr</r><re>I received the command</re><co>This is a comment about my response</co></m>");
	print "\n ------------------------------------------------------- \n";
	print "object from original, interpreted and re-converted to XML:\n";
	echo $messageFromRobot->XML->asXML();
	print "\n ------------------------------------------------------- \n";
	print "object from XML:\n";
	print_r($messageFromRobot);
	
	
	print "\n\n\n ------------------------------------------------------- \n";
	print "\n              messageToRobot test suite\n";
	print "\n ------------------------------------------------------- \n\n";
	$messageToRobot = new messageToRobot ('driver@9thsense.com','The Driver','robot@9thsense.com',
										'a');
	print "object to XML without argument and comment: \n";
	echo $messageToRobot->XML->asXML();
	
	
	$messageToRobot = new messageToRobot ('driver@9thsense.com','The Driver','robot@9thsense.com',
										'a','3');
	print "\n ------------------------------------------------------- \n";
	print "object to XML with argument but without comment: \n";
	echo $messageToRobot->XML->asXML();
	
	
	$messageToRobot = new messageToRobot ('driver@9thsense.com','The Driver','robot@9thsense.com',
										'a','3', 'This is a comment about how I am sending aaa using arguments');
	print "\n ------------------------------------------------------- \n";
	print "object to XML with argument and comment: \n";
	echo $messageToRobot->XML->asXML();
	
	$messageToRobot = new messageToRobot ("<?xml version='1.0' standalone='yes'?><m><d>driverAddr</d><dn>driverName</dn><r>robotAddr</r><c>commandChar</c><a>commandArguments</a></m>");
	print "\n ------------------------------------------------------- \n";
	print "object from original, interpreted and re-converted to XML:\n";
	echo $messageToRobot->XML->asXML();
	print "\n ------------------------------------------------------- \n";
	print "object from XML:\n";
	print_r($messageToRobot);
}

//robotMessagesTestSuite();
?>
