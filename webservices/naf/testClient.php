<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 21/06/15
 * Time: 10:32
 */

include "Services.php";



$soapClient = new Services();
	/*
$param = new InquiryRequest(4563,'65008880');
$p1 = new Inquiry($param);

$response = $soapClient->Inquiry($p1);
print_r($response);
	*/
//die();

echo "\nChiamata Adjustment";
/*								codice negozio,numero carta fedeltà,numero ordine,modifica ai punti,,,data ora
$param = new AdjustmentRequest(4563,'65008880','00078849',10,0,0,'2015-23-06 14:20:00');
$p1 = new Adjustment($param);
$response = $soapClient->Adjustment($p1);
print_r($response);
*/


echo "\nInquiry";
$param = new InquiryRequest(4563,'30654792');
$p1 = new Inquiry($param);

$response = $soapClient->Inquiry($p1);
print_r($response);

echo "\nChiamata Adjustment -10";
$param = new AdjustmentRequest(4563,'30654792','00215699',-559,0,0,'2016-02-03 15:00:00');
$p1 = new Adjustment($param);
$response = $soapClient->Adjustment($p1);
print_r($response);



/*
echo "\nInquiry";
$param = new InquiryRequest(4563,'00581091');
$p1 = new Inquiry($param);

$response = $soapClient->Inquiry($p1);
print_r($response);
*/