<?php
/* Jaxl (Jabber XMPP Library)
 *
 * Copyright (c) 2009-2010, Abhinav Singh <me@abhinavsingh.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Abhinav Singh nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

    /*
     * XEP: 0030 Service Discovery
     * Version: 2.4
     * Url: http://xmpp.org/extensions/xep-0030.html
    */
    class JAXL0030 {
    
        public static $ns = array('info'=>'http://jabber.org/protocol/disco#info', 'item'=>'http://jabber.org/protocol/disco#item');
        public static $category = false;
        public static $type = false;
        public static $name = false;
        public static $lang = false;
        
        public static function init($jaxl, $config=array()) {
            $jaxl->features[] = self::$ns['info'];
            $jaxl->features[] = self::$ns['item'];
            
            self::$category = isset($config['category']) ? $config['category'] : 'client';
            self::$type = isset($config['type']) ? $config['type'] : 'bot';
            self::$name = isset($config['name']) ? $config['name'] : 'Jaxl';
            self::$lang = isset($config['lang']) ? $config['lang'] : 'en';
            
            // register callbacks
            JAXLPlugin::add('jaxl_get_iq_get', array('JAXL0030', 'handleIq'));
        }
        
        public static function discoInfo($to, $from, $callback, $node=false, $jaxl) {
            $payload = '<query xmlns="'.self::$ns['info'].'"';
            if($node) $payload .= ' node="'.$node.'"/>';
            else $payload .= '/>';
            
            return XMPPSend::iq($jaxl, 'get', $payload, $to, $from, $callback);
        }
        
        public static function discoItem($to, $from, $callback, $node=false, $jaxl) {
            $payload = '<query xmlns="'.self::$ns['item'].'"';
            if($node) $payload .= ' node="'.$node.'"/>';
            else $payload .= '/>';
            
            return XMPPSend::iq($jaxl, 'get', $payload, $to, $from, $callback);
        }

        public static function handleIq($payload, $jaxl) {
            $xmlns = $payload['queryXmlns'];
            if($xmlns == self::$ns['info']) {
                $xml = '<query xmlns="'.$xmlns.'"';
                if(isset($payload['queryNode'])) $xml .= ' node="'.$payload['queryNode'].'"';
                $xml .= '>';
                
                $xml .= '<identity xml:lang="'.self::$lang.'"';
                $xml .= ' name="'.self::$name.'"';
                $xml .= ' category="'.self::$category.'"';
                $xml .= ' type="'.self::$type.'"/>';
                
                foreach($jaxl->features as $feature)
                    $xml .= '<feature var="'.$feature.'"/>';
                $xml .= '</query>';
                
                XMPPSend::iq($jaxl, 'result', $xml, $payload['from'], $payload['to'], false, $payload['id']);
            }
            else if($xmlns == self::$ns['item']) {
                
            }
            
            return $payload;
        }
    
    }
    
?>
