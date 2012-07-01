##   roster_nb.py
##         based on roster.py
##
##   Copyright (C) 2003-2005 Alexey "Snake" Nezhdanov
##         modified by Dimitur Kirov <dkirov@gmail.com>
##
##   This program is free software; you can redistribute it and/or modify
##   it under the terms of the GNU General Public License as published by
##   the Free Software Foundation; either version 2, or (at your option)
##   any later version.
##
##   This program is distributed in the hope that it will be useful,
##   but WITHOUT ANY WARRANTY; without even the implied warranty of
##   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
##   GNU General Public License for more details.

# $Id: roster.py,v 1.17 2005/05/02 08:38:49 snakeru Exp $


"""
Simple roster implementation. Can be used though for different tasks like
mass-renaming of contacts.
"""

from protocol import JID, Iq, Presence, Node, NodeProcessed, NS_MUC_USER, NS_ROSTER
from plugin import PlugIn

import logging
log = logging.getLogger('nbxmpp.roster_nb')


class NonBlockingRoster(PlugIn):
    """
    Defines a plenty of methods that will allow you to manage roster. Also
    automatically track presences from remote JIDs taking into account that
    every JID can have multiple resources connected. Does not currently support
    'error' presences. You can also use mapping interface for access to the
    internal representation of contacts in roster
    """

    def __init__(self, version=None):
        """
        Init internal variables
        """
        PlugIn.__init__(self)
        self.version = version
        self._data = {}
        self._set=None
        self._exported_methods=[self.getRoster]
        self.received_from_server = False

    def Request(self, force=0):
        """
        Request roster from server if it were not yet requested (or if the
        'force' argument is set)
        """
        if self._set is None:
            self._set = 0
        elif not force:
            return

        iq = Iq('get', NS_ROSTER)
        if self.version is not None:
            iq.setTagAttr('query', 'ver', self.version)
        id_ = self._owner.getAnID()
        iq.setID(id_)
        self._owner.send(iq)
        log.info('Roster requested from server')
        return id_

    def RosterIqHandler(self, dis, stanza):
        """
        Subscription tracker. Used internally for setting items state in internal
        roster representation
        """
        sender = stanza.getAttr('from')
        if not sender is None and not sender.bareMatch(
        self._owner.User + '@' + self._owner.Server):
            return
        query = stanza.getTag('query')
        if query:
            self.received_from_server = True
            self.version = stanza.getTagAttr('query', 'ver')
            if self.version is None:
                self.version = ''
            for item in query.getTags('item'):
                jid=item.getAttr('jid')
                if item.getAttr('subscription')=='remove':
                    if self._data.has_key(jid): del self._data[jid]
                    # Looks like we have a workaround
                    # raise NodeProcessed # a MUST
                log.info('Setting roster item %s...' % jid)
                if not self._data.has_key(jid): self._data[jid]={}
                self._data[jid]['name']=item.getAttr('name')
                self._data[jid]['ask']=item.getAttr('ask')
                self._data[jid]['subscription']=item.getAttr('subscription')
                self._data[jid]['groups']=[]
                if not self._data[jid].has_key('resources'): self._data[jid]['resources']={}
                for group in item.getTags('group'):
                    if group.getData() not in self._data[jid]['groups']:
                        self._data[jid]['groups'].append(group.getData())
        self._data[self._owner.User+'@'+self._owner.Server]={'resources': {}, 'name': None, 'ask': None, 'subscription': None, 'groups': None,}
        self._set=1
        # Looks like we have a workaround
        # raise NodeProcessed # a MUST. Otherwise you'll get back an <iq type='error'/>

    def PresenceHandler(self, dis, pres):
        """
        Presence tracker. Used internally for setting items' resources state in
        internal roster representation
        """
        if pres.getTag('x', namespace=NS_MUC_USER):
            return
        jid=pres.getFrom()
        if not jid:
            # If no from attribue, it's from server
            jid=self._owner.Server
        jid=JID(jid)
        if not self._data.has_key(jid.getStripped()): self._data[jid.getStripped()]={'name':None,'ask':None,'subscription':'none','groups':['Not in roster'],'resources':{}}
        if type(self._data[jid.getStripped()]['resources'])!=type(dict()):
            self._data[jid.getStripped()]['resources']={}
        item=self._data[jid.getStripped()]
        typ=pres.getType()

        if not typ:
            log.info('Setting roster item %s for resource %s...'%(jid.getStripped(), jid.getResource()))
            item['resources'][jid.getResource()]=res={'show':None,'status':None,'priority':'0','timestamp':None}
            if pres.getTag('show'): res['show']=pres.getShow()
            if pres.getTag('status'): res['status']=pres.getStatus()
            if pres.getTag('priority'): res['priority']=pres.getPriority()
            if not pres.getTimestamp(): pres.setTimestamp()
            res['timestamp']=pres.getTimestamp()
        elif typ=='unavailable' and item['resources'].has_key(jid.getResource()): del item['resources'][jid.getResource()]
        # Need to handle type='error' also

    def _getItemData(self, jid, dataname):
        """
        Return specific jid's representation in internal format. Used internally
        """
        jid = jid[:(jid+'/').find('/')]
        return self._data[jid][dataname]

    def _getResourceData(self, jid, dataname):
        """
        Return specific jid's resource representation in internal format. Used
        internally
        """
        if jid.find('/') + 1:
            jid, resource = jid.split('/', 1)
            if self._data[jid]['resources'].has_key(resource):
                return self._data[jid]['resources'][resource][dataname]
        elif self._data[jid]['resources'].keys():
            lastpri = -129
            for r in self._data[jid]['resources'].keys():
                if int(self._data[jid]['resources'][r]['priority']) > lastpri:
                    resource, lastpri=r, int(self._data[jid]['resources'][r]['priority'])
            return self._data[jid]['resources'][resource][dataname]

    def delItem(self, jid):
        """
        Delete contact 'jid' from roster
        """
        self._owner.send(Iq('set', NS_ROSTER, payload=[Node('item', {'jid': jid, 'subscription': 'remove'})]))

    def getAsk(self, jid):
        """
        Return 'ask' value of contact 'jid'
        """
        return self._getItemData(jid, 'ask')

    def getGroups(self, jid):
        """
        Return groups list that contact 'jid' belongs to
        """
        return self._getItemData(jid, 'groups')

    def getName(self, jid):
        """
        Return name of contact 'jid'
        """
        return self._getItemData(jid, 'name')

    def getPriority(self, jid):
        """
        Return priority of contact 'jid'. 'jid' should be a full (not bare) JID
        """
        return self._getResourceData(jid, 'priority')

    def getRawRoster(self):
        """
        Return roster representation in internal format
        """
        return self._data

    def getRawItem(self, jid):
        """
        Return roster item 'jid' representation in internal format
        """
        return self._data[jid[:(jid+'/').find('/')]]

    def getShow(self, jid):
        """
        Return 'show' value of contact 'jid'. 'jid' should be a full (not bare)
        JID
        """
        return self._getResourceData(jid, 'show')

    def getStatus(self, jid):
        """
        Return 'status' value of contact 'jid'. 'jid' should be a full (not bare)
        JID
        """
        return self._getResourceData(jid, 'status')

    def getSubscription(self, jid):
        """
        Return 'subscription' value of contact 'jid'
        """
        return self._getItemData(jid, 'subscription')

    def getResources(self, jid):
        """
        Return list of connected resources of contact 'jid'
        """
        return self._data[jid[:(jid+'/').find('/')]]['resources'].keys()

    def setItem(self, jid, name=None, groups=[]):
        """
        Rename contact 'jid' and sets the groups list that it now belongs to
        """
        iq = Iq('set', NS_ROSTER)
        query = iq.getTag('query')
        attrs = {'jid': jid}
        if name:
            attrs['name'] = name
        item = query.setTag('item', attrs)
        for group in groups:
            item.addChild(node=Node('group', payload=[group]))
        self._owner.send(iq)

    def setItemMulti(self, items):
        """
        Rename multiple contacts and sets their group lists
        """
        iq = Iq('set', NS_ROSTER)
        query = iq.getTag('query')
        for i in items:
            attrs = {'jid': i['jid']}
            if i['name']:
                attrs['name'] = i['name']
            item = query.setTag('item', attrs)
            for group in i['groups']:
                item.addChild(node=Node('group', payload=[group]))
        self._owner.send(iq)

    def getItems(self):
        """
        Return list of all [bare] JIDs that the roster is currently tracks
        """
        return self._data.keys()

    def keys(self):
        """
        Same as getItems. Provided for the sake of dictionary interface
        """
        return self._data.keys()

    def __getitem__(self, item):
        """
        Get the contact in the internal format. Raises KeyError if JID 'item' is
        not in roster
        """
        return self._data[item]

    def getItem(self, item):
        """
        Get the contact in the internal format (or None if JID 'item' is not in
        roster)
        """
        if self._data.has_key(item):
            return self._data[item]

    def Subscribe(self, jid):
        """
        Send subscription request to JID 'jid'
        """
        self._owner.send(Presence(jid, 'subscribe'))

    def Unsubscribe(self, jid):
        """
        Ask for removing our subscription for JID 'jid'
        """
        self._owner.send(Presence(jid, 'unsubscribe'))

    def Authorize(self, jid):
        """
        Authorize JID 'jid'. Works only if these JID requested auth previously
        """
        self._owner.send(Presence(jid, 'subscribed'))

    def Unauthorize(self, jid):
        """
        Unauthorise JID 'jid'. Use for declining authorisation request or for
        removing existing authorization
        """
        self._owner.send(Presence(jid, 'unsubscribed'))

    def getRaw(self):
        """
        Return the internal data representation of the roster
        """
        return self._data

    def setRaw(self, data):
        """
        Return the internal data representation of the roster
        """
        self._data = data
        self._data[self._owner.User + '@' + self._owner.Server] = {
                        'resources': {},
                        'name': None,
                        'ask': None,
                        'subscription': None,
                        'groups': None
        }
        self._set = 1

    def plugin(self, owner, request=1):
        """
        Register presence and subscription trackers in the owner's dispatcher.
        Also request roster from server if the 'request' argument is set. Used
        internally
        """
        self._owner.RegisterHandler('iq', self.RosterIqHandler, 'result', NS_ROSTER, makefirst = 1)
        self._owner.RegisterHandler('iq', self.RosterIqHandler, 'set', NS_ROSTER)
        self._owner.RegisterHandler('presence', self.PresenceHandler)
        if request:
            return self.Request()

    def _on_roster_set(self, data):
        if data:
            self._owner.Dispatcher.ProcessNonBlocking(data)
        if not self._set:
            return
        if not hasattr(self, '_owner') or not self._owner:
            # Connection has been closed by receiving a <stream:error> for ex,
            return
        self._owner.onreceive(None)
        if self.on_ready:
            self.on_ready(self)
            self.on_ready = None
        return True

    def getRoster(self, on_ready=None, force=False):
        """
        Request roster from server if neccessary and returns self
        """
        return_self = True
        if not self._set:
            self.on_ready = on_ready
            self._owner.onreceive(self._on_roster_set)
            return_self = False
        elif on_ready:
            on_ready(self)
            return_self = False
        if return_self or force:
            return self
        return None
