#!/usr/bin/env python
#
#	  Copyright (c) 2012, 9th Sense, Inc.
#	  All rights reserved.
#
#     Controller - Processes messages to and from robots  
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
import asyncore, socket, threading, SocketServer
import pprint
from optparse import OptionParser

from robotMessages import *

import MySQLdb

from sleekxmpp import ClientXMPP
from sleekxmpp.exceptions import IqError, IqTimeout

def microtime(get_as_float = False) :
    if get_as_float:
        return time.time()
    else:
        return '%f %d' % math.modf(time.time())


from socket import *
import thread
	
def handler(clientsocket, clientaddr):
	print "Accepted connection from: ", clientaddr

	while 1:
		data = clientsocket.recv(1024)
		if not data:
			break
		else:
			print data
			myList = data.split('|')
			mtr = messageToRobot('controller@9thsense.com', driverName='Controller', robotAddr=myList[0], commandChar=myList[2], commandArguments=myList[3], comment='')
			msg = "You sent me: %s" % mtr.commandChar
			clientsocket.send(msg)
	clientsocket.close()

if __name__ == "__main__":

	host = 'localhost'
	port = 49442
	buf = 4096

	addr = (host, port)

	serversocket = socket(AF_INET, SOCK_STREAM)

	serversocket.bind(addr)

	serversocket.listen(2)

	while 1:
		print "Server is listening for connections\n"

		clientsocket, clientaddr = serversocket.accept()
		thread.start_new_thread(handler, (clientsocket, clientaddr))
	serversocket.close()
	
#	 server.shutdown()
# 		

		
class Controller(ClientXMPP):

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
			syslog.syslog("" + s + ": Controller got message from robot: " + tostring(mfr.XML))
			if (mfr.responseValue == 'a'):
				mtr = messageToRobot('controller@9thsense.com', driverName='Controller', robotAddr=fromRobot, commandChar='!', commandArguments='alive', comment='')
				syslog.syslog("" + s + "Controller sent XMPP message to robot: " + tostring(mtr.XML))
			elif (mfr.responseValue == 'm'):
				mtr = messageToRobot('controller@9thsense.com', driverName='Controller', robotAddr=fromRobot, commandChar='!', commandArguments='received', comment='')
				robotMessageToUser (mfr.robotAddr, mfr.comment)
				syslog.syslog("" + s + ": Controller sent XMPP message to robot: " + tostring(mtr.XML))
			outputXMLString = tostring(mtr.XML)			
			msg.reply(outputXMLString).send()
