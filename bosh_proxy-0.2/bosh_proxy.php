<?php
/*
 * Version:     0.1
 * Date:        2011-05-13
 *
 * Author(s):   Luke Wahlmeier <lwahlmeier@gmail.com>
 *
 *              This program is free software; you can redistribute it and/or
 *              modify it under the terms of the GNU General Public License
 *              as published by the Free Software Foundation; either version
 *              3 of the License, or (at your option) any later version.
 */


/*      Configuration                                           */

$log['file']=false;                     #use file path (ie /tmp/bosh_proxy.log) if you want a log file
$log['level']=0;                        #this does nothing at the moment
$xmpp['server']="127.0.0.1";            #name or IP
$xmpp['port']=5222;
$xmpp['path']="";           #do not add the starting /
$xmpp['ssl']=false;                     #true=https, false=http


/*      Start of the proxy                                      */
/*      Dont modify unless you know what you are doing          */
function Fetch($url, $data)
{
    if (!function_exists('curl_init'))
    {
        echo "php curl support must be installed!!!";
        return false;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8')); 
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
    $f = curl_exec($ch);
    curl_close($ch);
    return $f;
}
if(!isset($HTTP_RAW_POST_DATA)) 
{
    $HTTP_RAW_POST_DATA = file_get_contents("php://input");
}
if($log['file']!==false)
{
    $fp=@fopen($log['file'], 'a');
}
$clientConnectInfo=print_r($_SERVER, true);
$clientPostInfo=print_r($HTTP_RAW_POST_DATA, true);
if($log['file']!==false  && $fp!==false)
{
    fwrite($fp, "CLIENT CONNECT:\n".$clientConnectInfo."\n----------\n");
    fwrite($fp, "IN FROM CLIENT:\n".$clientPostInfo."\n----------\n");
}
$url="";
if($xmpp['ssl']==true)
{
    $url="https://";
}
else
{
    $url="http://";
}
$serverResponseInfo=Fetch($url.$xmpp['server'].":".$xmpp['port']."/".$xmpp['path'], $HTTP_RAW_POST_DATA);
if($log['file']!==false && $fp!==false)
{
    fwrite($fp, "OUT TO CLIENT:\n".$serverResponseInfo."\n----------\n");
    fclose($fp);
}

header("Content-Type: text/xml; charset=utf-8");
echo $serverResponseInfo;


