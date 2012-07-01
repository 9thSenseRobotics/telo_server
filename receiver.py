#!/usr/bin/env python
#
#	  Copyright (c) 2012, 9th Sense, Inc.
#	  All rights reserved.
#
#     Receiver - Processes messages from robots  
#     by Alaina Hardie
#
#     This program is free software: you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation, either version 3 of the License, or
#     (at your option) any later version.
# 
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
# 
#     You should have received a copy of the GNU General Public License
#     along with this program.  If not, see <http://www.gnu.org/licenses/>.

import logging, sys, string, time, math, os, re, datetime
import pprint
import syslog
from optparse import OptionParser

from robotMessages import *
from pyc2dm import *

import MySQLdb

from sleekxmpp import ClientXMPP
from sleekxmpp.exceptions import IqError, IqTimeout

syslog.openlog(logoption=syslog.LOG_PID, facility=syslog.LOG_LOCAL0)

def robotMessageToUser(robotAddress, message):
# The next time a user sends a command to this robot--even the regular Ajax checkin--they will
# receive this message from the robot.
# 	$r = mysql_escape_string($robotAddress);
# 	$m = mysql_escape_string($message);

   cursor = conn.cursor ()
#    cursor.execute ("SELECT VERSION()")
#    row = cursor.fetchone ()
#    print "server version:", row[0]
#    cursor.close ()
   string = "INSERT INTO driving_message_from_robot (xmpp_username, message) VALUES ('" + robotAddress + "', '" + message + "') ON DUPLICATE KEY UPDATE message = '" + message + "';"
   cursor.execute (string)
   cursor.close ()
   conn.commit()

def getAndroidInfo(robotName):
	cursor = conn.cursor (MySQLdb.cursors.DictCursor)
	part = robotName.partition('@')
	print "RobotName is " + robotName
	query = "SELECT * FROM phones WHERE name=\"" + part[0] + "\" OR  name=\"" + robotName + "\"  ";
	print query
	cursor.execute (query)
	row = cursor.fetchone ()
	cursor.close ()
	return (row)


class Receiver(ClientXMPP):

	def __init__(self, jid, password):
		ClientXMPP.__init__(self, jid, password)

		self.use_signals(signals=None)
		self.add_event_handler("session_start", self.session_start)
		self.add_event_handler("message", self.message)

		self.register_plugin('xep_0030') # Service Discovery
		self.register_plugin('xep_0199') # XMPP Ping
	
	def session_start(self, event):
		self.send_presence()

		# Most get_*/set_* methods from plugins use Iq stanzas, which
		# can generate IqError and IqTimeout exceptions
		try:
			self.get_roster()
		except IqError as err:
			logging.error('There was an error getting the roster')
			logging.error(err.iq['error']['condition'])
			self.disconnect()
		except IqTimeout:
			logging.error('Server is taking too long to respond')
			self.disconnect()

	def message(self, msg):
		global pub
		if msg['type'] in ('chat', 'normal'):
			print "From is " + str(msg['from'])
			s = str(msg['from'])
			tup = s.partition("/")
			print "Tup[0] is " + tup[0]
			fromRobot = str(tup[0])
			print "fromRobot is " + fromRobot
			theString = msg['body']
			mfr = messageFromRobot(theString)
			syslog.syslog("" + s + ": Receiver got message from robot: " + tostring(mfr.XML))
			if (mfr.responseValue == 'a'):
				mtr = messageToRobot('receiver@9thsense.com', driverName='Receiver', robotAddr=fromRobot, commandChar='!', commandArguments='alive', comment='')
				syslog.syslog("" + s + "Receiver sent XMPP message to robot: " + tostring(mtr.XML))
			elif (mfr.responseValue == 'm'):
				mtr = messageToRobot('receiver@9thsense.com', driverName='Receiver', robotAddr=fromRobot, commandChar='!', commandArguments='received', comment='')
				robotMessageToUser (mfr.robotAddr, mfr.comment)
				syslog.syslog("" + s + ": Receiver sent XMPP message to robot: " + tostring(mtr.XML))
			outputXMLString = tostring(mtr.XML)			
			msg.reply(outputXMLString).send()
			phone = getAndroidInfo(fromRobot)
			if (len(str(phone['registration']))):
	 			phoneReg = str(phone['registration'])
				deviceId = str(phone['deviceid'])
			syslog.syslog("" + s + ": Receiver sending message to device " + deviceId + "at reg " + phoneReg + ": " + tostring(mtr.XML))
			try:
				c = C2DM(client_token=token, source="COMPANY.APP.VERSION")
				c.send_notification(device_id=phoneReg, collapse_key=0, data={'payload': outputXMLString})
				syslog.syslog("" + s + "Receiver sent C2DM message to robot: " + tostring(mtr.XML))
			except Exception as e: 
				syslog.syslog("" + s + "Receiver failed to send C2DM message  to robot: " + tostring(mtr.XML))
				print 'Handling C2DM error:', e

if __name__ == '__main__':

	logging.basicConfig(level=logging.ERROR, format='%(levelname)-8s %(message)s')
	#logging.basicConfig(level=logging.DEBUG, format='%(levelname)-8s %(message)s')

	conn = MySQLdb.connect (host = "127.0.0.1",
						   user = "9thsense",
						   passwd = "~FERmion",
						   db = "9thsense")

	c = C2DM(email="telebotphone@gmail.com", password="9thsense&", source="COMPANY.APP.VERSION")
	try:
		token = c.get_client_token()
		print "Got C2DM token"
	except C2DMException as e:
		raise # couldn't get a client token
	
	# Then in the future use C2DM(client_token="YOUR TOKEN", source="COMPANY.APP.VERSION")

	xmpp = Receiver('receiver@9thsense.com', '9thsense')
	xmpp.connect()
	print "Connected, yo"
	try:
		xmpp.process(block=False)
	except KeyboardInterrupt, SystemExit:
		quit()
		conn.close ()


