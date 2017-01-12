<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/06/15
 * Time: 23:32
 */

ini_set("soap.wsdl_cache_enabled", 0);
//$client = new SoapClient('http://104.155.22.149/soap/delivery.wsdl?wsdl', array('trace' => 1));
$client = new SoapClient('http://104.155.21.52/soap/DeliveryService.php?WSDL', array('trace' => 1));
//$client = new SoapClient('http://104.155.22.149/soap/delivery.wsdl', array('trace' => 1));


print_r($client->__getFunctions());

//Test GetListDeliveries
/*
$obj = new stdClass();
//$obj->orderNumber = "00155859";
$obj->orderStatus="ENTERED_PICKUP";
$obj->numLines = 30;
$obj->offSet=1;

$info = $client->__soapCall("GetListDeliveries", array($obj));
print $client->__getLastRequest();
print_r($info);

*/
/*
//Test GetDetailsDelivery
$obj = new stdClass();
$obj->deliveryNumber = "00112331";
$obj->headerId = "00112331";
$obj->alertR = "1";
$obj->alertG = "12";
$obj->alertY = "500";
$info = $client->__soapCall("GetDetailsDelivery", array($obj));
print $client->__getLastRequest();
print_r($info);

*/

//TEST REFUND





$obj = new stdClass();
$obj->reasonCode = "COINCASA_OTHER";
$obj->headerId = "00112331";
//$obj->refundStatus = "TO_BE_REFUND";
$obj->refundStatus = "REFUND";
$obj->clerckName = "pippo@test.it";
$obj->refundNote = "prova nota di refund";

$main = new stdClass();
$main->refundDelivery = $obj;

$info = $client->__soapCall("RefundDelivery", array($main));
print $client->__getLastRequest();
print_r($info);
