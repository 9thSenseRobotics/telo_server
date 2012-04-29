#!/usr/bin/env python
#
#	  Copyright (c) 2012, 9th Sense, Inc.
#	  All rights reserved.
#
#     Robot's XMPP client - publishes messages on /SkypeChat 
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
from std_msgs.msg import String
from optparse import OptionParser
import roslib; roslib.load_manifest('captureSkypeChat')
import rospy

from sleekxmpp import ClientXMPP
from sleekxmpp.exceptions import IqError, IqTimeout

pub = rospy.Publisher('SkypeChat', String)

def microtime(get_as_float = False) :
    if get_as_float:
        return time.time()
    else:
        return '%f %d' % math.modf(time.time())

class EchoBot(ClientXMPP):

	def __init__(self, jid, password):
		ClientXMPP.__init__(self, jid, password)

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
		if msg['type'] in ('chat', 'normal'):
			#print(" " + msg['body'] +"\n")
			theString = msg['body']
			myItems = string.split(theString, '|')
			mySentTime = float (myItems[1])
			myCurrentTime = microtime(True)
			stringToSend = "I got this message (" + myItems[2].strip() + ") in " + repr(myCurrentTime - mySentTime) + " seconds"
			print (stringToSend)
			rospy.loginfo(messageBody)
			pub.publish(myItems[2].strip())
			msg.reply(stringToSend).send()

if __name__ == '__main__':
# Ideally use optparse or argparse to get JID, 
# password, and log level.

	logging.basicConfig(level=logging.ERROR, format='%(levelname)-8s %(message)s')
	rospy.init_node('XMPPListener')

	xmpp = EchoBot('alaina@9thsense.com', '2cool4u')
	xmpp.connect()
	try:
		xmpp.process(block=True)
	except KeyboardInterrupt:
		quit()

