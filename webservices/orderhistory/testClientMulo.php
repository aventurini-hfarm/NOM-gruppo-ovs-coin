<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/06/15
 * Time: 23:32
 */
//wget http://localhost/orderHistory/services/orderHistory/GetOrderByWebUserId?site=CC&UserId=00019601
//wget http://localhost/orderHistory/services/orderHistory/?wsdl
$client = new SoapClient('http://192.168.165.243:8080/services/orderHistory?wsdl', array('trace' => 1));
print_r($client->__getFunctions());

$obj = new stdClass();
$obj->webuserid = "00008070";
$obj->site = "5463";
$info = $client->__soapCall("GetOrderByWebUserId", array($obj));
print_r($info);

echo "\nDettaglio ordine";
$obj->ordernumber = "00078849";
$info = $client->__soapCall("GetOrderLinesByWebUserId", array($obj));
print_r($info);

