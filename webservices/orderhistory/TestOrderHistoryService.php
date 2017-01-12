<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 11/06/15
 * Time: 11:02
 */


require_once '/home/OrderManagement/omdb/ShipmentDBHelper.php';
require_once '/home/OrderManagement/dw2om/orders/MagentoOrderHelper.php';
require_once "/home/OrderManagement/common/KLogger.php";

const DATE_FORMAT = 'Ymd'; #per la format dell'oggetto Date

function GetOrderByWebUserId($param) {

    $log = new KLogger('/tmp/web_service.log',KLogger::DEBUG);
    $log->LogDebug($param->webuserid);
    $log->LogDebug($param->site);
    $helper = new MagentoOrderHelper();
    $lista = $helper->getOrderHistoryByCustomerId($param->webuserid,$param->site);

    $res = array();
//    $xml = new DOMDocument();
//    $dateInfoElement = $xml->createElement("order");

//    $xmlNode = $xml->createElement("brand","CC");
//    $dateInfoElement->appendChild($xmlNode);

//    $xml->appendChild($dateInfoElement);
    $header = "Content-Type:text/xml";

    header($header);
//    array_push($res, $xml->saveXML());
//    return ($xml->saveXML());

//        $obj = new stdClass();
//        $obj->brand="CC";

//    return $obj;

    foreach ($lista as $increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $shDbHelper = new ShipmentDBHelper();
        $objShipment = $shDbHelper->getShipment($order->getDwOrderNumber());

        $obj = new stdClass();
        $obj->brand="CC";

        //$xmlNode = $xml->createElement("brand","CC");
        //$dateInfoElement->appendChild($xmlNode);

        $obj->orderNumber=$order->getDwOrderNumber();
        $obj->orderChannel="";
        $obj->gift="N";
        $obj->orderDate=$order->getCreatedAtDate()->toString(DATE_FORMAT);
        $obj->cancelledDate="";
        $obj->cancelReason="";

        if ($order->getStatus()=='complete')
            $obj->orderStatus="Spedito";
        else
            $obj->orderStatus="In lavorazione";

        $obj->deliveryDate=$objShipment->delivery_date;
        $obj->shipmentDate=$objShipment->shipping_date;

        $shipping_method_selected = $order->getData('shipping_method');
        switch ($shipping_method_selected) {
            case "excellence_Forniture":
                $obj->shippingMethod='Forniture';
                break;
            default:
                $obj->shippingMethod='Standard';
        }


        $obj->totalAmount=$order->getBaseTaxAmount();
        $obj->taxAmount=$order->getTaxAmount();
        $obj->shippingChargesAmount=""; //TODO shipping amount
        $obj->taxshippingChargesAmount=""; //TODO tax shipping amount

        $order_billing_address= $order->getBillingAddress();
        $obj->billToAddress1=$order_billing_address->getStreet(1);
        $obj->billToAddress2="";
        $obj->billToAddress3="";
        $obj->billToAddress4="";
        $obj->billToCity=$order_billing_address->getCity();
        $obj->billToPostalCode=$order_billing_address->getPostcode();
        $obj->billToState="";
        $obj->billToProvince=$order_billing_address->getRegion();
        $obj->billToCounty="";
        $obj->billToCountry=$order_billing_address->getCountryId();
        $obj->billToContact="";
        $obj->billToEmail="";
        $obj->billToPhone=$order_billing_address->getTelephone();
        $obj->billToFirstName=$order_billing_address->getFirstname();
        $obj->billToLastName=$order_billing_address->getLastname();
        $obj->billToTitle="";

        $order_shipping_address= $order->getShippingAddress();
        $obj->shipToFirstName=$order_shipping_address->getFirstname();
        $obj->shipToLastName=$order_shipping_address->getLastname();
        $obj->shipToTitle="";
        $obj->shipToAddress1=$order_shipping_address->getStreet(1);
        $obj->shipToAddress2="";
        $obj->shipToAddress3="";
        $obj->shipToAddress4="";
        $obj->shipToCity=$order_shipping_address->getCity();
        $obj->shipToPostalCode=$order_shipping_address->getPostcode();
        $obj->shipToState="";
        $obj->shipToProvince=$order_shipping_address->getRegion();
        $obj->shipToCounty="";
        $obj->shipToCountry=$order_shipping_address->getCountryId();
        $obj->shipToContact="";
        $obj->shipToPhone=$order_shipping_address->getTelephone();
        $obj->customerNumber=$param->webuserid;
        $obj->registryId="";

        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
            ->setOrderFilter($order)
            ->load();
        foreach ($shipmentCollection as $shipment){
            // This will give me the shipment IncrementId, but not the actual tracking information.
            foreach($shipment->getAllTracks() as $tracknum)
            {
                $tracknums[]=$tracknum->getNumber();
            }

        }

        $obj->trackingInformation=$objShipment->first_track;

        array_push($res, $obj);

    }



    return $res;
}

function GetOrderLinesByWebUserId($param) {
    $log = new KLogger('/tmp/web_service.log',KLogger::DEBUG);
    $log->LogDebug("Details: ".$param->ordernumber);



    $helper = new MagentoOrderHelper();
    $increment_id = $helper->getOrderIdByDWId($param->ordernumber);
    $log->LogDebug("Increment_ID: ".$increment_id);
    $orderObj = new stdClass();



    $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
    $shDbHelper = new ShipmentDBHelper();
    $objShipment = $shDbHelper->getShipment($order->getDwOrderNumber());

    $obj = new stdClass();
    $obj->brand="CC";
    $obj->orderNumber=$order->getDwOrderNumber();
    $obj->orderChannel="";
    $obj->gift=false;
    $obj->orderDate=$order->getCreatedAtDate()->toString(DATE_FORMAT);
    $obj->cancelledDate="";
    $obj->cancelReason="";
    if ($order->getStatus()=='complete')
        $obj->orderStatus="Spedito";
    else
        $obj->orderStatus="In lavorazione";


    $obj->deliveryDate=$objShipment->delivery_date;
    $obj->shipmentDate=$objShipment->shipping_date;

    $shipping_method_selected = $order->getData('shipping_method');
    switch ($shipping_method_selected) {
        case "excellence_Forniture":
            $obj->shippingMethod='Forniture';
            break;
        default:
            $obj->shippingMethod='Standard';
    }


    $obj->totalAmount=$order->getBaseTaxAmount();
    $obj->taxAmount=$order->getTaxAmount();
    $obj->shippingChargesAmount=""; //TODO shipping amount
    $obj->taxshippingChargesAmount=""; //TODO tax shipping amount

    $order_billing_address= $order->getBillingAddress();
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

    if ($order_billing_address) {
        $obj->billToAddress1=$order_billing_address->getStreet(1);
        $obj->billToAddress2="";
        $obj->billToAddress3="";
        $obj->billToAddress4="";
        $obj->billToCity=$order_billing_address->getCity();
        $obj->billToPostalCode=$order_billing_address->getPostcode();
        $obj->billToState="";
        $obj->billToProvince=$order_billing_address->getRegion();
        $obj->billToCounty="";
        $obj->billToCountry=$order_billing_address->getCountryId();
        $obj->billToContact="";
        $obj->billToEmail="";
        $obj->billToPhone=$order_billing_address->getTelephone();
        $obj->billToFirstName=$order_billing_address->getFirstname();
        $obj->billToLastName=$order_billing_address->getLastname();
        $obj->billToTitle="";
    }

    $order_shipping_address= $order->getShippingAddress();
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

    if ($order_shipping_address) {
        $obj->shipToFirstName=$order_shipping_address->getFirstname();
        $obj->shipToLastName=$order_shipping_address->getLastname();
        $obj->shipToTitle="";
        $obj->shipToAddress1=$order_shipping_address->getStreet(1);
        $obj->shipToAddress2="";
        $obj->shipToAddress3="";
        $obj->shipToAddress4="";
        $obj->shipToCity=$order_shipping_address->getCity();
        $obj->shipToPostalCode=$order_shipping_address->getPostcode();
        $obj->shipToState="";
        $obj->shipToProvince=$order_shipping_address->getRegion();
        $obj->shipToCounty="";
        $obj->shipToCountry=$order_shipping_address->getCountryId();
        $obj->shipToContact="";
        $obj->shipToPhone=$order_shipping_address->getTelephone();
    }

    $obj->customerNumber=$param->webuserid;
    $obj->registryId="";

    $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
        ->setOrderFilter($order)
        ->load();
    foreach ($shipmentCollection as $shipment){
        // This will give me the shipment IncrementId, but not the actual tracking information.
        foreach($shipment->getAllTracks() as $tracknum)
        {
            $tracknums[]=$tracknum->getNumber();
        }

    }

    $obj->trackingInformation=$objShipment->first_track;

    $orderObj->order = $obj;




    //ottiene le linee ordine
    $log->LogDebug("Getting Details: ".$increment_id);
    $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
    $lines_list = array();
    foreach ($lines as $line) {
        $log->LogDebug("SKU: ".$line['sku']);
        $lineObj = new stdClass();
        $lineObj->sku=$line['sku'];
        $lineObj->longDescription=$line['description'];
        $lineObj->itemStyle="";
        $lineObj->quantity=$line['order_quantity'];
        $lineObj->unitPrice= $line['unit_price'];
        $lineObj->extendedPrice="";
        $lineObj->taxAmount="";
        $lineObj->trackingInformation=$objShipment->first_track;
        $lineObj->billToAddress=$order_billing_address->getStreet(1);
        $lineObj->billToContact=$order_billing_address->getFirstname()." ".$order_billing_address->getLastname();
        $lineObj->shipToAddress=$order_shipping_address->getStreet(1);
        $lineObj->shipToContact=$order_shipping_address->getFirstname()." ".$order_shipping_address->getLastname();
        $lineObj->lineStatus=""; //TODO mettere lo stato dell'ordine
        $lineObj->shipmentDate="";
        $lineObj->deliveredDate="";
        $lineObj->returnDate="";
        $lineObj->canceledDate="";
        $lineObj->enableRefundFlag="false";


        array_push($lines_list, $lineObj);
    }

    //$orderObj->line = $lines_list;

    $orderObj->order->line = $lines_list;

    return $orderObj;
}

// Set up the PHP SOAP server
$server = new SoapServer("test.wsdl");
// Add the exposable functions
$server->addFunction("GetOrderByWebUserId");
$server->addFunction("GetOrderLinesByWebUserId");

// Handle the request
$server->handle();
$obj = new stdClass();
$obj->webuserid = "00083302";
$obj->site = "5463";
$t = GetOrderByWebUserId($obj);
print_r($t);

//$obj = new stdClass();
//$obj->ordernumber = "00078849";
//$obj->webuserid = "00008070";
//$obj->site = "5463";
echo "\nGetDetails";
$obj->ordernumber = "00047156";
$t = GetOrderLinesByWebUserId($obj);
print_r($t);