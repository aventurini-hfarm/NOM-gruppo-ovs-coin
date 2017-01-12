<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 25/07/15
 * Time: 19:54
 */

$xml = new DOMDocument();
$dateInfoElement = $xml->createElement("order");

$xmlNode = $xml->createElement("brand","CC");
$dateInfoElement->appendChild($xmlNode);

$xml->appendChild($dateInfoElement);
$header = "Content-Type:text/xml";

header($header);
array_push($res, $xml->saveXML());
print_r($xml->saveXML());