<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/06/15
 * Time: 23:11
 */

require_once '/home/OrderManagement/omdb/ShipmentDBHelper.php';
require_once '/home/OrderManagement/dw2om/orders/MagentoOrderHelper.php';


function GetListDeliveries($param = null) {

    $brand = $param->brand;
    $storeCodePick =$param->storeCodePick; //RICERCA
    $orderNumber = $param->orderNumber; //RICERCA
    $customerFullName = $param->customerFullName;
    $customerEmail = $param->customerEmail;
    $orderStatus = $param->orderStatus;  //CAMPO DI RICERCA
    $dateFrom = $param->dateFrom; //RICERCA
    $dateTo = $param->dateTo; //RICERCA
    $orderBy = $param->orderBy;
    $orderType = $param->orderType;
    $alertR = $param->alertR;
    $alertG = $param->alertG;
    $alertY = $param->alertY;
    $offSet = $param->offSet;
    $numLines = $param->numLines;


//    $dw_order_no = DeliveryDBHelper::getDWOrderNumberByDeliveryId(ltrim($deliveryNumber,'0'));
//    $helper = new MagentoOrderHelper();
//    $increment_id = $helper->getOrderIdByDWId($dw_order_no);

//    $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');

    $obj = new stdClass();
    $obj->totalLines="1";
    $lines = array();

    $line = new stdClass();
    $line->brand="CC";
    $line->orderStatus="SHIPPED_TO_STORE";
    $line->storeCodePick="006";
    $line->orderAmount="1200";
    $line->billToPhoneNumber="";
    $line->orderNumber="123";
    $line->orderDate="2015-05-30T09:00:00";
    $line->trackingUrl="http://www.test.it";
    $line->deliveryNumber="10000";
    $line->firstName="Vincenzo";
    $line->lastName="Sambucaro";
    $line->shippingDate=null;
    $line->deliveredDate="2015-06-05T09:00:00";
    $line->custDeliveredDate=null;
    $line->fidelityFlag="";
    $line->alertRGY="G";
    $line->nColli="1";
    $line->headerId="10";

    array_push($lines, $line);

    $obj->delivery = $lines;

    $ret = new stdClass();
    $ret->deliveryResponse =  $obj;

    $header = "Content-Type:text/xml";

    header($header);

    $helper = new DeliveryServiceHelper();
    $result = $helper->getListaOrdini($param);

    return $result;

}

function GetDetailsDelivery($param = null) {

    $header = "Content-Type:text/xml";

    header($header);

    $helper = new DeliveryServiceHelper();
    $result = $helper->getDetailsDelivery($param);

    return $result;

    $headerId = $param->headerId;
    $deliveryNumber =$param->deliveryNumber; //RICERCA



    $obj = new stdClass();

    $obj->storeCodePick="ALL";
    $obj->orderStatus="SHIPPED_TO_STORE";
    $obj->orderNumber="123";
    $obj->nColli="1";
    $obj->trackingUrl="";
    $obj->firstName="Vincenzo";
    $obj->lastName="Sambucaro";
    $obj->billToPhoneNumber="3357900189";
    $obj->orderDate="2015-05-30T09:00:00";
    $obj->shippingDate="";
    $obj->deliveredDate=""; //data in cui è arrivato in negozio
    $obj->custDeliveredDate="";
    $obj->alertRGY="R";
    $obj->accountNumber="";
    $obj->billToAddress="Via Solferino 40";
    $obj->paymentType="CC";
    $obj->paymentDetail="MASTERCARD";
    $obj->invoiceNumber="";
    $obj->fidelityCard="";
    $obj->loyaltyPoints="";
    $obj->custDeliveredNote="";
    $obj->totalQty="1";
    $obj->totalAmount="1200";
    $obj->refundNote="NOTE PROVA";
    $obj->enableRefundFlag="";
    $obj->reasonCode="";




    //ottiene le linee ordine
    $increment_id="100003340";
    $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
    $lines_list = array();
    foreach ($lines as $line) {

        $lineObj = new stdClass();
        $lineObj->sku=$line['sku'];
        $lineObj->longDescription=$line['description'];

        $lineObj->quantity=$line['order_quantity'];
        $lineObj->unitSellingPrice= $line['unit_price'];
        $lineObj->promotion="";
        $lineObj->promotionAmount="";



        $desc = $line['description'];
        $qty = $line['order_quantity'];
        $unit_price =  $line['unit_price'];
        $discount_value = $line['discount_value'];
        if ($discount_value=='0.00') $discount_value = $unit_price * $qty;
        $total = $discount_value;

        $lineObj->lineAmount=$total;

        array_push($lines_list, $lineObj);
    }

    $obj->lines = $lines_list;

    $ret = new stdClass();
    $ret->detail =  $obj;

    $header = "Content-Type:text/xml";

    header($header);

    return $ret;
}

function UpdateDelivery($param = null) {

    $header = "Content-Type:text/xml";

    header($header);

    $helper = new DeliveryServiceHelper();
    $result = $helper->updateDelivery($param);

    return $result;
}

function RefundDelivery($param = null) {

    $header = "Content-Type:text/xml";

    header($header);

    $helper = new DeliveryServiceHelper();
    $result = $helper->refundDelivery($param);

    return $result;
}

$message = file_get_contents("php://input");

$handle = fopen("/tmp/richiesta2.txt",  'a');
fwrite($handle, $message);
fclose($handle);

// Set up the PHP SOAP server
$server = new SoapServer("delivery.wsdl",array('cache_wsdl' => WSDL_CACHE_NONE));
// Add the exposable functions
$server->addFunction("GetListDeliveries");
$server->addFunction("GetDetailsDelivery");
$server->addFunction("UpdateDelivery");
$server->addFunction("RefundDelivery");

// Handle the request
$server->handle();