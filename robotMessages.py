"""
	robotMessages.py - specifies PHP classes for the XML exchanged between robots and 
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
	 Once instantiated, you can reference the contents by theObject.driverAddr, 
	 theObject.robotAddr, etc.
	 
	 To output the contents as XML for sending, use:
	 	mfr = messageFromRobot(theXMLStringToParse)
		outputXMLString = tostring(mfr.XML)
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
	 	
"""

import inspect, time, math

import pprint

from xml.etree.ElementTree import ElementTree, fromstring, tostring, dump, SubElement

def microtime(get_as_float = False) :
	if get_as_float:
		return str(time.time()).replace('.', '')
	else:
		return '%f %d' % math.modf(time.time())
        
class robotMessages(object):
	"""Base class for all robot messages. Specifies the properties contained within the XML as well
	as $this->XML, which is a SimpleXML element that can be interacted with normally.
	//
	See PHP's documentation for more info on the SimpleXML library:
	//		http://php.net/manual/en/book.simplexml.php
	//
	Also provides the default constructor to count arguments because because PHP doesn't 
	support method overloading, and sets the microtime() timestamp of its instantiation 
	"""
	# for messages to robot from driver
	driverAddr = ''
	driverName = ''
	robotAddr = ''
	commandChar = ''
	commandArguments = ''
	comment = ''

	# for messages from robot to driver
	responseValue = ''
	
	XML  = ''# simpleXML type
	
	timeStamp = '' #when it was created  - time.microtime() (float)
# endclass robotMessages	

class messageToRobot(robotMessages): # extends robotMessages
	"""For messages that are sent from a driver or controller to the robot
	
	Usage:
	
		mtr =  messageToRobot(rawXMLString) # build from the raw XML
		mtr =  messageToRobot(driverAddr, driverName, robotAddr, commandChar [[,$commandArguments] [,$comment]]);
	"""
	def __init__(self, firstArgument, driverName='', robotAddr='', commandChar='', commandArguments='', comment=''):
	# single-argument: build from XML
	# multiple-argument: build this class from its component data with a command and a comment
		if (driverName == ''):
			tree = fromstring(firstArgument)
			ts = SubElement(tree, 't')
			ts.text = microtime(True)
			self.XML = tree

			self.driverAddr = self.XML.findtext('d')
			self.driverName = self.XML.findtext('dn')
			self.robotAddr = self.XML.findtext('r')
			self.commandChar = self.XML.findtext('c')
 			self.commandArguments = self.XML.findtext('a')
 			self.comment = self.XML.findtext('co')
		else:
			self.driverAddr = firstArgument
			self.driverName = driverName
			self.robotAddr = robotAddr
			self.commandChar = commandChar
 			self.commandArguments = commandArguments
 			self.comment = comment
			
			tree = fromstring('<m></m>')
			ts = SubElement(tree, 't')
			ts.text = microtime(True) + "00"
			ds = SubElement(tree, 'd')
			ds.text = self.driverAddr
			dns = SubElement(tree, 'dn')
			dns.text = self.driverName
			rs = SubElement(tree, 'r')
			rs.text = self.robotAddr
			cs = SubElement(tree, 'c')
			cs.text = self.commandChar
			aas = SubElement(tree, 'a')
			aas.text = self.commandArguments
			cos = SubElement(tree, 'co')
			cos.text = self.comment
			self.XML = tree
# endclass messageToRobot

class messageFromRobot(robotMessages): # extends robotMessages
	"""For messages that are sent from a robot to something else 
	
	Usage:
	
		mfr =  messageFromRobot(rawXMLString) # build from the raw XML
		mfr =  messageFromRobot(driverAddr, robotAddr, responseValue [,$comment]);
	"""
	def __init__(self, firstArgument, robotAddr='', responseValue='', comment=''):
	# single-argument: build from XML
	# multiple-argument: build this class from its component data with optional  comment
		if (robotAddr == '' and responseValue == '' and comment == ''):
			tree = fromstring(firstArgument)
			ts = SubElement(tree, 't')
			ts.text = microtime(True)
			self.XML = tree

			self.driverAddr = self.XML.findtext('d')
			self.robotAddr = self.XML.findtext('r')
			self.responseValue = self.XML.findtext('re')
 			self.comment = self.XML.findtext('co')
		else:
			self.driverAddr = firstArgument
			self.robotAddr = robotAddr
			self.responseValue = responseValue
 			self.comment = comment
			
			tree = fromstring('<m></m>')
			ts = SubElement(tree, 't')
			ts.text = microtime(True)
			ds = SubElement(tree, 'd')
			ds.text = self.driverAddr
			rs = SubElement(tree, 'r')
			rs.text = self.robotAddr
			res = SubElement(tree, 're')
			res.text = self.responseValue
			cos = SubElement(tree, 'co')
			cos.text = self.comment
			self.XML = tree
# endclass messageFromRobot
