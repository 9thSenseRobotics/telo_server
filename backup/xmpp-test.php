#!/usr/bin/env php
<?php

// activate full error reporting
//error_reporting(E_ALL & E_STRICT);

include 'xmpphp/XMPP.php';

#Use XMPPHP_Log::LEVEL_VERBOSE to get more logging for error reports
#If this doesn't work, are you running 64-bit PHP with < 5.2.6?
$conn = new XMPPHP_XMPP('9thsense.com', 5222, 'controller', '9thsense', 'xmpphp', '9thsense.com', $printlog=true, $loglevel=XMPPHP_Log::LEVEL_INFO);
$conn->autoSubscribe();

$vcard_request = array();

try {
    $conn->connect();
    while(!$conn->isDisconnected()) {
    	$payloads = $conn->processUntil(array('message', 'presence', 'end_stream', 'session_start', 'vcard'));
    	foreach($payloads as $event) {
    		$pl = $event[1];
    		switch($event[0]) {
    			case 'message': 
    				print "---------------------------------------------------------------------------------\n";
    				print "Message from: {$pl['from']}\n";
    				print $pl['body'] . "\n";
    				print "---------------------------------------------------------------------------------\n";
					$cmd = explode(' ', $pl['body']);
    			case 'session_start':
    			    print "Session Start\n";
// 			    	$conn->getRoster(); 
    				$conn->presence($status="Cheese!");
    			break;
// 				case 'vcard':
// 					// check to see who requested this vcard
// 					$deliver = array_keys($vcard_request, $pl['from']);
// 					// work through the array to generate a message
// 					print_r($pl);
// 					$msg = '';
// 					foreach($pl as $key => $item) {
// 						$msg .= "$key: ";
// 						if(is_array($item)) {
// 							$msg .= "\n";
// 							foreach($item as $subkey => $subitem) {
// 								$msg .= "  $subkey: $subitem\n";
// 							}
// 						} else {
// 							$msg .= "$item\n";
// 						}
// 					}
// 					// deliver the vcard msg to everyone that requested that vcard
// 					foreach($deliver as $sendjid) {
// 						// remove the note on requests as we send out the message
// 						unset($vcard_request[$sendjid]);
//     					$conn->message($sendjid, $msg, 'chat');
// 					}
// 				break;
    		}
    	}
    }
} catch(XMPPHP_Exception $e) {
    die($e->getMessage());
}
