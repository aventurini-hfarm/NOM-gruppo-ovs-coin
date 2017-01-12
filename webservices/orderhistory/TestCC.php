<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 07/09/15
 * Time: 17:41
 */

require_once "ClickCollectHelper.php";
require_once "/home/OrderManagement/email/RitiroCCEmailHelper.php";

/*
 * 07/09/2015 solo per test poi togliere in produzione
 */
$helper = new ClickCollectHelper();
$status ="IN_STORE";
$deliveryNumber = "12345";
$clerkName = "pippo@test.it";
$custDeliveredNote="Consegnato al cliente";
$increment_id = '000000016';

if ($status=='IN_STORE') {
    $helper->addCustomAttribute($deliveryNumber, $clerkName, 'STATUS',$status);
    $helper->updateMagentoStoreOrderStatus($deliveryNumber, $status);
    $ritiroCcHelper = new RitiroCCEmailHelper();
    $ritiroCcHelper->inviaEmailRitiroCC($increment_id);
}
if ($status=='DELIVERED') {
    $helper->addCustomAttribute($deliveryNumber, $clerkName, 'STATUS',$status);
    $helper->addCustomAttribute($deliveryNumber, $clerkName, 'custDeliveredNote',$custDeliveredNote);
    $helper->updateMagentoStoreOrderStatus($deliveryNumber, $status);
}