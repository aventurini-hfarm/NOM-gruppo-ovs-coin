<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 24/04/15
 * Time: 16:41
 */
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class GetOrderDetails {

    public function run($increment_id){
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $shipping_address = $order->getShippingAddress();
        //print_r($shipping_address);
        $shipping_method_selected = $order->getData('shipping_method');
        echo "\nShipping method: ".$shipping_method_selected;
        print_r($order->getData());

    }
}

$t = new GetOrderDetails();
$t->run('100000083');