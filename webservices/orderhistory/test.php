<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/06/15
 * Time: 23:11
 */

 function GetOrderByWebUserId($param) {

    $res = array();
    $obj = new stdClass();
     $obj->brand="prova";
     $obj->orderNumber="123";
     $obj->orderChannel=$param->webuserid;
     $obj->gift=$param->site;
     $obj->orderDate="";
     $obj->cancelledDate="";
     $obj->cancelReason="";
     $obj->orderStatus="";
     $obj->deliveryDate="";
                    $obj->shipmentDate="";
     $obj->shippingMethod="";
     $obj->totalAmount="";
     $obj->taxAmount="";
     $obj->shippingChargesAmount="";
     $obj->taxshippingChargesAmount="";
     $obj->billToAddress1="";
     $obj->billToAddress2="";
     $obj->billToAddress3="";
     $obj->billToAddress4="";
     $obj->billToCity="";
     $obj->billToPostalCode="";
     $obj->billToState="";
     $obj->billToProvince="";
     $obj->billToCounty="";
     $obj->billToCountry="";
     $obj->billToContact="";
     $obj->billToEmail="";
     $obj->billToPhone="";
     $obj->billToFirstName="";
     $obj->billToLastName="";
     $obj->billToTitle="";
     $obj->shipToFirstName="";
     $obj->shipToLastName="";
     $obj->shipToTitle="";
     $obj->shipToAddress1="";
     $obj->shipToAddress2="";
     $obj->shipToAddress3="";
     $obj->shipToAddress4="";
     $obj->shipToCity="";
     $obj->shipToPostalCode="";
     $obj->shipToState="";
     $obj->shipToProvince="";
     $obj->shipToCounty="";
     $obj->shipToCountry="";
     $obj->shipToContact="";
     $obj->shipToPhone="";
     $obj->customerNumber="";
     $obj->registryId="";
     $obj->trackingInformation="";
     array_push($res, $obj);
     return $res;

}
// Set up the PHP SOAP server
$server = new SoapServer("test.wsdl");
// Add the exposable functions
$server->addFunction("GetOrderByWebUserId");

// Handle the request
$server->handle();