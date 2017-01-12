<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 07/07/15
 * Time: 09:36
 */
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

require_once "/home/OrderManagement/webservices/naf/Services.php";
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

define ('codice_ecommerce',4563);
$timestamp = date('Y-m-d H:m:s');
//echo $timestamp;
//die();

$action = $_POST['action'];
if ($action=='changeLoyalties') {
    $numero_ordine = $_POST['numero_ordine'];
    $carta_loyalty = $_POST['carta_loyalty'];
    $variazione_punti = $_POST['variazione_punti'];
    $variazione_wallet = $_POST['variazione_wallet'];


    $soapClient = new Services();
    //$param = new InquiryRequest(codice_ecommerce, $carta_loyalty);
    //$p1 = new Inquiry($param);

    //$response = $soapClient->Inquiry($p1);
    //print_r($response);

    if (!$variazione_wallet) $variazione_wallet = 0;

    $timestamp = date('Y-m-d H:m:s');
    $param = new AdjustmentRequest(codice_ecommerce,$carta_loyalty, $numero_ordine,$variazione_punti, $variazione_wallet, 0, $timestamp);
    $p1 = new Adjustment($param);
    $response = $soapClient->Adjustment($p1);
    echo 'OK';
    return;


} elseif ($action=='order_retransmission') {

    $increment_id = $_POST['numero_ordine'];
    //echo "\nNumero_ordine: ".$numero_ordine;
    $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
    //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
    //$order->save();
    $order->setData('state', "pending");
    $order->setStatus("pending");
    $history = $order->addStatusHistoryComment('Ordine da inviare al magazzino', false);
    $history->setIsCustomerNotified(false);
    $order->save();
    echo "OK";
    return;
}


