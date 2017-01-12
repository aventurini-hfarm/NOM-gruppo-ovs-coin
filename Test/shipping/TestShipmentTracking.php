<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 25/04/15
 * Time: 13:58
 */

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class TestShipmentTracking {

    public function test($increment_id){
       // $order=Mage::getModel('sales/order')->load($orderId);

        $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);

        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
            ->setOrderFilter($order)
            ->load();
        foreach ($shipmentCollection as $shipment){
            // This will give me the shipment IncrementId, but not the actual tracking information.
            foreach($shipment->getAllTracks() as $tracknum)
            {
                //$tracknums[]=$tracknum->getNumber();
                print_r($tracknum->getData());
            }

        }

    }
}

$t = new TestShipmentTracking();
$t->test('100000248');