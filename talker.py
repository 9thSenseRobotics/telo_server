#!/usr/bin/env python
#
#	  Copyright (c) 2012, 9th Sense, Inc.
#	  All rights reserved.
#
#     Talker - Sends messages to robots and waits for responses
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


class Receiver(ClientXMPP):

	def __init__(self, jid, password):
		ClientXMPP.__init__(self, jid, password)

		self.use_signals(signals=None)
		self.add_event_handler("session_start", self.session_start)

		self.register_plugin('xep_0030') # Service Discovery
		self.register_plugin('xep_0199') # XMPP Ping
	
	def session_start(self, event):
		self.send_presence()

		# Most get_*/set_* methods from plugins use Iq stanzas, which
		# can generate IqError and IqTimeout exceptions
		try:
			self.get_roster()
			self.send_message(mto="helo.five@9thsense.com", mbody='Hello', mtype='chat')
			self.disconnect()
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

if __name__ == '__main__':

	logging.basicConfig(level=logging.ERROR, format='%(levelname)-8s %(message)s')
	#logging.basicConfig(level=logging.DEBUG, format='%(levelname)-8s %(message)s')

	xmpp = Receiver('receiver@9thsense.com', '9thsense')
	xmpp.connect()
	print "Connected, yo"
	try:
		xmpp.process(block=False)
	except KeyboardInterrupt, SystemExit:
		quit()
		conn.close ()


