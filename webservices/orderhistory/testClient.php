<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/06/15
 * Time: 23:32
 */

$client = new SoapClient('http://104.155.22.149/soap/test.wsdl?WSDL', array('trace' => 1));
$client = new SoapClient('http://104.155.22.149/soap/OrderHistoryService.php?WSDL', array('trace' => 1));

print_r($client->__getFunctions());

$obj = new stdClass();
$obj->webuserid = "00083302";
$obj->site = "5463";
$info = $client->__soapCall("GetOrderByWebUserId", array($obj));
print $client->__getLastRequest();
print_r($info);


echo "\nDettaglio ordine";
$obj->ordernumber = "00083302";
$info = $client->__soapCall("GetOrderLinesByWebUserId", array($obj));
print_r($info);


