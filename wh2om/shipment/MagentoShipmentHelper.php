<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 25/04/15
 * Time: 13:58
 */

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
require_once realpath(dirname(__FILE__))."/../../dw2om/orders/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../omdb/CountryDBHelper.php";
Mage::app();

class MagentoShipmentHelper {

    private $log;

    public function __construct()
    {
        $this->log = new KLogger('/var/log/nom/magento_shipment_helper.log',KLogger::DEBUG);
    }


    public function shippingSimulator($order_no) {
        // $order=Mage::getModel('sales/order')->load($orderId);
        $magOrderHelper = new MagentoOrderHelper();

        $increment_id = $magOrderHelper->getOrderIdByDWId($order_no);
        if (!$increment_id) {
            echo "Ordine non trovato: " . $order_no;
            return;
        }
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



                $shipmentCarrierCode = "FAKE CORRIERE";
                $shipmentCarrierTitle = "FAKE CORRIERE";

                $shipmentTrackingNumber = "FAKE 1234";

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


                echo "Ordine aggiornato con successo";

            }

        } else {
            echo "Order can ship false";
        }
    }

    public function shippingFullOrder(ShippingObject $shipmentObject){
       // $order=Mage::getModel('sales/order')->load($orderId);
        $magOrderHelper = new MagentoOrderHelper();

        $increment_id = $magOrderHelper->getOrderIdByDWId($shipmentObject->order_no);
        if (!$increment_id) {
            $this->log->LogDebug("Ordine non trovato: " . $shipmentObject->order_no);
            return;
        }
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

                /*  RINO 21/07/2016 in OVS si usa estero_light
                $shipmentCarrierCode = 'SDA';
                $shipmentCarrierTitle = 'SDA';

                $order_shipping_address= $order->getShippingAddress();
                $country_id = $order_shipping_address->country_id;
                if ($country_id!='IT') {
                   $cDetails = CountryDBHelper::getCountryDetails($country_id);
                   $shipmentCarrierCode = "UPS";
                   $shipmentCarrierTitle = "UPS";

                   if ($cDetails) {
                       $shipmentCarrierCode = $cDetails->corriere;
                       $shipmentCarrierTitle = $cDetails->corriere;
                   }
                }
                */

                /*  RINO 21/07/2016 in OVS si usa estero_light */
                $order_shipping_address= $order->getShippingAddress();
                $country_id = $order_shipping_address->country_id;
                $cDetails = CountryDBHelper::getCountryDetails($country_id);
                $shipmentCarrierCode = $cDetails->corriere;
                $shipmentCarrierTitle = $cDetails->corriere;
                /*  RINO 21/07/2016 in OVS si usa estero_light */

                $shipmentTrackingNumber = $shipmentObject->lettera_vettura;

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

                //Aggiorna Ordine con dati aggiuntivi
                //Solo il subinvetory 0001 manda i dati
                /*
                if ($shipmentObject->subinventory=='0001')
                   $this->updateOrder($increment_id, $shipmentObject);
                */

                $this->log->LogDebug("Ordine aggiornato con successo");

            }

        } else {

            $this->log->LogDebug("Order can ship false");
        }
    }

/*
    private function updateOrder($increment_id, ShippingObject $shipmentObject) {



        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $order->setData('ncolli',$shipmentObject->ncolli);
        $order->setData('lettera_vettura',$shipmentObject->lettera_vettura);
        $order->setData('first_track',$shipmentObject->first_track);
        $order->setData('shipping_date',$shipmentObject->shipping_date);
        $order->save();
    }
*/

    public function shippingItem($increment_id, $itemSku, $qta_shipped){

        $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);


        $itemId = null;
        foreach($order->getAllItems() as $eachOrderItem){

            if ($eachOrderItem->getSku()==$itemSku) {
                $itemId = $eachOrderItem->getId();
            }
        }
        if (!$itemId) {
            $this->log->LogError('Errore item non trovato: '.$itemSku);
            die ('Errore Item non trovato: '.$itemSku);

        }

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
}

//$t = new MagentoShipmentHelper();
//$res = $t->getOrderIdByDWId('00130358');
//print_r($res);
//$t->shippingFullOrder('100000042');
//$t->shippingItem('100000040','0001');
