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

class TestOrderShipping {

    public function shippingFullOrder($increment_id){
       // $order=Mage::getModel('sales/order')->load($orderId);

        $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);

        $qty=array();
        foreach($order->getAllItems() as $eachOrderItem){

            $Itemqty=0;
            $Itemqty = $eachOrderItem->getQtyOrdered()
                - $eachOrderItem->getQtyShipped()
                - $eachOrderItem->getQtyRefunded()
                - $eachOrderItem->getQtyCanceled();
            $qty[$eachOrderItem->getId()]=$Itemqty;

        }

        /*
        echo "<pre>";
        print_r($qty);
        echo "</pre>";
        */
        /* check order shipment is prossiable or not */

        $email=true;
        $includeComment=true;
        $comment="test Shipment";

        if ($order->canShip()) {
            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            /* prepare to create shipment */
            $shipment = $order->prepareShipment($qty);
            if ($shipment) {
                $shipment->register();
                $shipment->addComment($comment, $email && $includeComment);
                $shipment->getOrder()->setIsInProcess(true);

                $shipmentCarrierCode = 'SDA';
                $shipmentCarrierTitle = 'SDA';
                $shipmentTrackingNumber = "101010";

                $arrTracking = array(
                    'carrier_code' => $shipmentCarrierCode,
                    'title' => $shipmentCarrierTitle,
                    'number' => $shipmentTrackingNumber,
                );

                $track = Mage::getModel('sales/order_shipment_track')->addData($arrTracking);
                $shipment->addTrack($track);
                try {
                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($shipment)
                        ->addObject($shipment->getOrder())
                        ->save();
                    //$shipment->sendEmail($email, ($includeComment ? $comment : ''));
                } catch (Mage_Core_Exception $e) {
                    var_dump($e);
                }

            }

        }
    }


    public function shippingItem($increment_id, $itemSku){

        $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);


        $itemId = null;
        foreach($order->getAllItems() as $eachOrderItem){

            if ($eachOrderItem->getSku()==$itemSku) {
                $itemId = $eachOrderItem->getId();
            }
        }
        if (!$itemId) die ('Errore Item non trovato: '.$itemSku);

        $qty=array();
        $eachOrderItem = Mage::getModel('sales/order_item')->load($itemId);

        $Itemqty=0;
        $Itemqty = $eachOrderItem->getQtyOrdered()
            - $eachOrderItem->getQtyShipped()
            - $eachOrderItem->getQtyRefunded()
            - $eachOrderItem->getQtyCanceled();
        $qty[$eachOrderItem->getId()]=$Itemqty;

        $email=true;
        $includeComment=true;
        $comment="test Shipment";

        if ($order->canShip()) {
            /* @var $shipment Mage_Sales_Model_Order_Shipment */
            /* prepare to create shipment */
            $shipment = $order->prepareShipment($qty);
            if ($shipment) {
                $shipment->register();
                $shipment->addComment($comment, $email && $includeComment);
                $shipment->getOrder()->setIsInProcess(true);
                try {
                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($shipment)
                        ->addObject($shipment->getOrder())
                        ->save();
                    $shipment->sendEmail($email, ($includeComment ? $comment : ''));
                } catch (Mage_Core_Exception $e) {
                    var_dump($e);
                }

            }

        }
    }


    public function getOrderIdByDWId($dw_id) {
        $data = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToSelect('increment_id')
            ->addAttributeToFilter('dw_order_number',$dw_id)->load()->getData();

        if (is_array($data)) {

            return $data[0]['increment_id'];
        } else
            return $data['increment_id'];

    }}

$t = new TestOrderShipping();
//$t->shippingFullOrder('100000042');
//$t->shippingItem('100000040','0001');
$id = $t->getOrderIdByDWId('00130358');
$t->shippingFullOrder($id);