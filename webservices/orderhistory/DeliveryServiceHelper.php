<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 31/07/15
 * Time: 08:40
 */

date_default_timezone_set('Europe/Rome');
require_once "/home/OrderManagement/common/KLogger.php";
require_once "/home/OrderManagement/omdb/OrderDBHelper.php";
require_once "/home/OrderManagement/omdb/ShipmentDBHelper.php";
require_once "/home/OrderManagement/email/RitiroCCEmailHelper.php";

require_once "ClickCollectHelper.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');

Mage::app();

class DeliveryServiceHelper {

    private $log;
    public function __construct() {
        $this->log = new KLogger('/var/log/nom/delivery_service_helper.log',KLogger::DEBUG);
    }

    public function getListaOrdini($filtro) {

        $storeCodePick =$filtro->storeCodePick; //RICERCA
        $orderNumber = $filtro->orderNumber; //RICERCA
        $orderStatus = $filtro->orderStatus;  //CAMPO DI RICERCA
        $dateFrom = $filtro->dateFrom; //RICERCA
        $dateTo = $filtro->dateTo; //RICERCA


        //costruisce SQL
        $sql ="SELECT increment_id, dw_order_number FROM sales_flat_order WHERE shipping_description='ClickAndCollect'";
        $first = false;

        if ($storeCodePick && $storeCodePick!='ALL') {
            $sql_tmp=" store_code_pick='".$storeCodePick."'";
            if ($first)
                $sql =$sql ." WHERE ".$sql_tmp;
            else
                $sql =$sql ." AND ".$sql_tmp;
            $first = false;
        }

        if ($orderNumber) {

            $sql_tmp=" dw_order_number='".$orderNumber."'";
            if ($first)
                $sql =$sql ." WHERE ".$sql_tmp;
            else
                $sql =$sql ." AND ".$sql_tmp;
            $first = false;

        }

        if ($orderStatus) {

            $tmp_order_status = "complete";
            if ($orderStatus=="SHIPPED_TO_STORE")
                $sql_tmp=" status='".$tmp_order_status."'";
            elseif ($orderStatus=="ENTERED_PICKUP")
                $sql_tmp=" (status='processing' OR status='pending' OR status='onhold')";
            elseif ($orderStatus=="DELIVERED_TO_CUST")
                $sql_tmp=" store_order_status='DELIVERED'";
            else
                $sql_tmp=" store_order_status='".$orderStatus."'";
            if ($first)
                $sql =$sql ." WHERE ".$sql_tmp;
            else
                $sql =$sql ." AND ".$sql_tmp;
            $first = false;

        }

        if ($dateFrom && $dateTo) {
            //oppure usare il campo dw_order_date_time
            $tmp_date_from=date('Y-m-d 00:00:00',strtotime($dateFrom));
            $tmp_date_to=date('Y-m-d 23:59:59',strtotime($dateTo));
            $sql_tmp=" (created_at BETWEEN '$tmp_date_from' AND '$tmp_date_to')";
            if ($first)
                $sql =$sql ." WHERE ".$sql_tmp;
            else
                $sql =$sql ." AND ".$sql_tmp;
            $first = false;

        }elseif ($dateFrom){
            $tmp_date_from=date('Y-m-d 00:00:00',strtotime($dateFrom));
            $tmp_date_to = date('Y-m-d 23:59:59');
            $sql_tmp=" (created_at BETWEEN '$tmp_date_from' AND '$tmp_date_to')";
            if ($first)
                $sql =$sql ." WHERE ".$sql_tmp;
            else
                $sql =$sql ." AND ".$sql_tmp;
            $first = false;

        }elseif ($dateTo) {
            $tmp_date_from="2000-01-01 00:00:00";
            $tmp_date_to = date('Y-m-d 23:59:59',strtotime($dateTo));
            $sql_tmp=" (created_at BETWEEN '$tmp_date_from' AND '$tmp_date_to')";
            if ($first)
                $sql =$sql ." WHERE ".$sql_tmp;
            else
                $sql =$sql ." AND ".$sql_tmp;
            $first = false;

        }

        $sql_no_limit = $sql;

        $limit = $filtro->numLines;
        $offset = $filtro->offSet - 1;
        if ($limit)
        $sql .=" LIMIT ".$limit;

        if ($offset)
            $sql .= " OFFSET ".$offset;

        //echo "\nSQL: ".$sql;
        $this->log->LogDebug("Resulting sql: ".$sql);

        $lista_ordini_no_limit = $this->executeQuery($sql_no_limit);

        $numero_record_totali = sizeof($lista_ordini_no_limit);

        $lista_ordini  = $this->executeQuery($sql);


        $result = $this->GetListDeliveries($lista_ordini, $numero_record_totali);

        //print_r($lista_ordini);
        //print_r($result);

        return $result;

    }


    private function executeQuery($sql) {
        $con = OMDBManager::getMagentoConnection();
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $obj = new stdClass();
            $obj->increment_id = $row->increment_id;
            $obj->dw_order_number = $row->dw_order_number;
            array_push($lista, $obj);
        }

        OMDBManager::closeConnection($con);
        return $lista;
    }


    private function GetListDeliveries($lista_ordini, $numero_record_totali = 30) {

        $shipmentDbHelper = new ShipmentDBHelper();


        //$magHelper = new MagentoOrderHelper();
        $lines = array();

        $masterObj = new stdClass();

        foreach ($lista_ordini as $obj) {
            $increment_id = $obj->increment_id;
            $dw_order_number = $obj->dw_order_number;
            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');

            $line = new stdClass();
            $line->brand="CC";
            /*
             * STATUS
             * TO_BE_REFUND
             * REFUND
             * IN_STORE
             * DELIVERED_TO_CUST
             * SHIPPED_TO_STORE
             */

            //$this->log->LogDebug("Resulting status: ".$order->getStatus()." - ".$increment_id);
            if ($order->getStatus()=="canceled") continue;
            //if ($dw_order_number=='00178460') continue;


            if ($order->getStatus()=='complete' && $order->getData('store_order_status')=='')
                $line->orderStatus='SHIPPED_TO_STORE';
            else if ($order->getStatus()=='processing' || $order->getStatus()=='pending' || $order->getStatus()=='onhold')
                $line->orderStatus='ENTERED_PICKUP';
            else
                $line->orderStatus=$order->getData('store_order_status');

            if ($order->getData('store_order_status')=="DELIVERED")  $line->orderStatus="DELIVERED_TO_CUST";
            $line->storeCodePick=$order->getData('store_code_pick');
            $line->orderAmount=$order->base_grand_total;
            $line->billToPhoneNumber="";
            $line->orderNumber=$dw_order_number;
           $line->orderDate="2015-05-30T09:00:00";
            $tmp_order_date = substr($order->getDwOrderDatetime(),0,19);

            //$this->log->LogDebug("Resulting date: ".$order->getDwOrderDatetime()." - ".$increment_id);
            //$line->orderDate=date("Y-m-dTH:i:s", strtotime($order->getDwOrderDatetime()));
            $line->orderDate = $tmp_order_date;
            //$this->log->LogDebug("Resulting new date: ". $line->orderDate." - ".$increment_id);
            $line->trackingUrl="https://www.mysda.it/SDAServiziEsterniWeb2/faces/SDAElencoSpedizioni.jsp?user=ecommercecoin&idritiro=".ltrim($dw_order_number,'0');

            if ($line->orderStatus=="ENTERED_PICKUP")
                $line->trackingUrl="";

            $line->trackingUrl="";
            $line->deliveryNumber=$dw_order_number;
            $line->firstName=$order->customer_firstname;
            $line->lastName=$order->customer_lastname;
            $shipObj = $shipmentDbHelper->getShipment($dw_order_number);
            $shipDate = $shipObj->shipping_date;
            if ($shipDate) {
                $tmp_ship_date = substr($shipDate,0,10)."T"."00:00:00";
                $line->shippingDate=$tmp_ship_date; //data di shipping
            }
            //$line->shippingDate=date("Y-m-dTH:i:s", strtotime($shipDate)); //data di shipping

            //$this->log->LogDebug("Resulting shipping date: ".$shipDate." - ".$increment_id);
            $line->deliveredDate=null; //data arrivo in negozio

            if ($line->orderStatus=="IN_STORE" || $line->orderStatus=="DELIVERED_TO_CUST" || $line->orderStatus=="TO_BE_REFUND" || $line->orderStatus=="REFUND") {
                $data_operazione = "";
                $ccHelper = new ClickCollectHelper();
                $data_operazione = $ccHelper->getCustomAttribute($dw_order_number,'STATUS','IN_STORE')->data_operazione;
                $tmp_do = date("Y-m-d", strtotime($data_operazione));
               // $this->log->LogDebug("Resulting delivery date: ".$data_operazione." - ".$increment_id);
                //$tmp_data_operazione = substr($data_operazione,0,10)."T"."00:00:00";
                $tmp_data_operazione = $tmp_do."T"."00:00:00";
               // $this->log->LogDebug("Resulting delivery date: ".$tmp_data_operazione." - ".$increment_id);
                $line->deliveredDate=$tmp_data_operazione;
            }

            $line->custDeliveredDate=null;
            if ($line->orderStatus=="DELIVERED_TO_CUST" || $line->orderStatus=="TO_BE_REFUND" || $line->orderStatus=="REFUND") {
                $data_operazione = "";
                $ccHelper = new ClickCollectHelper();
                $data_operazione = $ccHelper->getCustomAttribute($dw_order_number,'STATUS','DELIVERED')->data_operazione;
                $tmp_do = date("Y-m-d", strtotime($data_operazione));
                //$this->log->LogDebug("Resulting delivery date: ".$data_operazione." - ".$increment_id);
                //$tmp_data_operazione = substr($data_operazione,0,10)."T"."00:00:00";
                $tmp_data_operazione = $tmp_do."T"."00:00:00";
                $this->log->LogDebug("Resulting delivery date: ".$tmp_data_operazione." - ".$increment_id);
                $line->custDeliveredDate=$tmp_data_operazione;
            }

            $line->fidelityFlag="";
            $line->alertRGY="G";
            $line->nColli=$shipObj->ncolli;
            if (!$line->nColli) $line->nColli = 1;
            $line->headerId=$dw_order_number;

            $this->log->LogDebug("Adding to list: " . $dw_order_number);
            array_push($lines, $line);




        }

        #$masterObj->totalLines=sizeof($lista_ordini);
        //$masterObj->totalLines=sizeof($lines) ;
        $masterObj->totalLines=$numero_record_totali ;
        $masterObj->delivery = $lines;

        $ret = new stdClass();
        $ret->deliveryResponse =  $masterObj;

        return $ret;

    }

    public function getDetailsDelivery($param) {
        $headerId = $param->headerId;
        $deliveryNumber =$param->deliveryNumber;

        $shipmentDbHelper = new ShipmentDBHelper();
        $helper = new MagentoOrderHelper();
        $increment_id = $helper->getOrderIdByDWId($headerId);
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $shipObj = $shipmentDbHelper->getShipment($headerId);
        $shipDate = $shipObj->shipping_date;


        $codice_cliente_dw = $order->getDwCustomerId();

        $scontrino = $order->getData('bill_number');

        $fattura = $order->getData('invoice_number');

        $obj = new stdClass();

        $obj->storeCodePick=$order->getData('store_code_pick');

        if ($order->getStatus()=='complete' && $order->getData('store_order_status')=='')
            $obj->orderStatus='SHIPPED_TO_STORE';
        else if ($order->getStatus()=='processing' || $order->getStatus()=='pending' || $order->getStatus()=='onhold')
            $obj->orderStatus='ENTERED_PICKUP'; //ENTERED_PICKUP
        else
            $obj->orderStatus=$order->getData('store_order_status');

        if ($order->getData('store_order_status')=="DELIVERED")  $obj->orderStatus="DELIVERED_TO_CUST";
        $obj->orderNumber=$headerId;
        $obj->trackingUrl="";
        $obj->trackingUrl="https://www.mysda.it/SDAServiziEsterniWeb2/faces/SDAElencoSpedizioni.jsp?user=ecommercecoin&idritiro=".ltrim($headerId,'0');

        if ($obj->orderStatus=="ENTERED_PICKUP")
            $obj->trackingUrl="";

        $obj->firstName=$order->customer_firstname;
        $obj->lastName=$order->customer_lastname;
        $obj->billToPhoneNumber="";
        $obj->orderDate="2015-05-30T09:00:00";
        $tmp_order_date = substr($order->getDwOrderDatetime(),0,19);

        $obj->orderDate = $tmp_order_date;

        $obj->shippingDate=null;
        if ($shipDate) {
            $tmp_ship_date = substr($shipDate,0,10)."T"."00:00:00";
            $obj->shippingDate=$tmp_ship_date; //data di shipping
        }

        $obj->deliveredDate=null; //data arrivo in negozio
        if ($obj->orderStatus=="IN_STORE" || $obj->orderStatus=="DELIVERED_TO_CUST" || $obj->orderStatus=="TO_BE_REFUND" || $obj->orderStatus=="REFUND") {
            $data_operazione = "";
            $ccHelper = new ClickCollectHelper();
            $data_operazione = $ccHelper->getCustomAttribute($headerId,'STATUS','IN_STORE')->data_operazione;
            $tmp_do = date("Y-m-d", strtotime($data_operazione));
            $this->log->LogDebug("Resulting delivery date: ".$data_operazione." - ".$increment_id);
            //$tmp_data_operazione = substr($data_operazione,0,10)."T"."00:00:00";
            $tmp_data_operazione = $tmp_do."T"."00:00:00";
            $this->log->LogDebug("Resulting delivery date: ".$tmp_data_operazione." - ".$increment_id);
            $obj->deliveredDate=$tmp_data_operazione;
        }

        $obj->custDeliveredDate=null;

        if ($obj->orderStatus=="DELIVERED_TO_CUST" || $obj->orderStatus=="TO_BE_REFUND" || $obj->orderStatus=="REFUND") {
            $data_operazione = "";
            $ccHelper = new ClickCollectHelper();
            $data_operazione = $ccHelper->getCustomAttribute($headerId,'STATUS','DELIVERED')->data_operazione;
            $tmp_do = date("Y-m-d", strtotime($data_operazione));
            $this->log->LogDebug("Resulting delivery date: ".$data_operazione." - ".$increment_id);
            //$tmp_data_operazione = substr($data_operazione,0,10)."T"."00:00:00";
            $tmp_data_operazione = $tmp_do."T"."00:00:00";
            $this->log->LogDebug("Resulting delivery date: ".$tmp_data_operazione." - ".$increment_id);
            $obj->custDeliveredDate=$tmp_data_operazione;

            $obj->custDeliveredNote="";
            $custDeliveryNote = $ccHelper->getCustomAttributeValue($headerId,'custDeliveredNote')->valore;
            $obj->custDeliveredNote= $custDeliveryNote;
        }


        $order_billing_address= $order->getBillingAddress();
        $obj->billToAddress="";
        if ($order_billing_address) {
            $obj->billToAddress=$order_billing_address->getStreet(1);
        }

        $obj->alertRGY="G";
        $obj->accountNumber=$codice_cliente_dw;

        $payment = $order->getPayment();
        $payment_method_selected = $payment->getMethod();
        if ($payment_method_selected=='ccsave') {
            $obj->paymentType="CC";
            $obj->paymentDetail=$payment->getCcType();

        }elseif ($payment_method_selected=='cashondelivery') {
            $obj->paymentType="CO";
            $obj->paymentDetail="CONTANTI";

        }
        else {
            $obj->paymentType="PP";
            $obj->paymentDetail="PAYPAL";
        }



        $obj->invoiceNumber=$fattura;

        $orderDBHelper = new OrderDBHelper($headerId);
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();



        $tessera_fidelity='';
        if ($order_custom_attributes['loyaltyCard'])
            $tessera_fidelity = str_pad($order_custom_attributes['loyaltyCard'], 9, '0',STR_PAD_LEFT);

        $punti_guadagnati = $order_custom_attributes['rewardPoints'];
        if (!$punti_guadagnati) $punti_guadagnati="";
        $punti_spesi = $order_custom_attributes['spentPoints'];

        $obj->fidelityCard=$tessera_fidelity;
        $obj->loyaltyPoints=$punti_guadagnati;


        $obj->totalAmount=$order->base_grand_total;

        $obj->refundNote="";
        $obj->enableRefundFlag=1;
        $obj->reasonCode="";

        if ($obj->orderStatus=="TO_BE_REFUND" || $obj->orderStatus=="REFUND") {
            $data_operazione = "";
            $ccHelper = new ClickCollectHelper();

            $obj->refundNote="";
            $refundNote = $ccHelper->getCustomAttributeValue($headerId,'refundNote')->valore;
            $obj->refundNote= $refundNote;

            $reasonCode = $ccHelper->getCustomAttributeValue($headerId,'reasonCode')->valore;
            $obj->reasonCode= $reasonCode;

        }

        $obj->nColli=$shipObj->ncolli;
        if (!$obj->nColli) $obj->nColli = 1;




        //ottiene le linee ordine

        $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
        $lines_list = array();
        $obj->totalQty=sizeof($lines);
        foreach ($lines as $line) {

            $lineObj = new stdClass();
            $lineObj->sku=$line['sku'];
            $lineObj->longDescription=$line['description'];

            $lineObj->quantity=$line['order_quantity'];
            $lineObj->unitSellingPrice= $line['unit_price'];
            if (array_key_exists($line, 'item_dw_promo_id'))
                $lineObj->promotion=$line['item_dw_promo_id'];
            else
                $lineObj->promotion='';
            $lineObj->promotionAmount=$line['discount_value'];



            $desc = $line['description'];
            $qty = $line['order_quantity'];
            $unit_price =  $line['unit_price'];
            $discount_value = $line['discount_value'];
            //if ($discount_value=='0.00') $discount_value = $unit_price * $qty;
            $total = $unit_price * $qty - $discount_value;
            $this->log->LogDebug("qty: " . $qty);
            $this->log->LogDebug("unit_price: " . $unit_price);
            $this->log->LogDebug("discount_value: " . $discount_value." before: ".$line['discount_value']);
            $this->log->LogDebug("total: " . $total);

            $lineObj->lineAmount=$total;

            array_push($lines_list, $lineObj);
        }

        $obj->lines = $lines_list;

        $ret = new stdClass();
        $ret->detail =  $obj;


        return $ret;

    }

    public function updateDelivery($param) {
        $deliveryNumber = $param->updateDeliveries->deliveryNumber;
        $status = $param->updateDeliveries->orderStatus;
        $clerkName = $param->updateDeliveries->clerckName;
        $custDeliveredNote = $param->updateDeliveries->custDeliveredNote;
        $helper = new MagentoOrderHelper();
        $increment_id = $helper->getOrderIdByDWId($deliveryNumber);

        $this->log->LogDebug("Update Delivery: ".$deliveryNumber." , ".$status." , ".$clerkName);

        $helper = new ClickCollectHelper();
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

        $obj = new stdClass();
        $obj->status="OK";

        $ret = new stdClass();
        $ret->update = $obj;

        return $ret;
    }

    public function refundDelivery($param) {
        $headerId = $param->refundDelivery->headerId;
        $refundStatus = $param->refundDelivery->refundStatus;
        $reasonCode = $param->refundDelivery->reasonCode;
        $refundNote = $param->refundDelivery->refundNote;
        $clerkName = $param->refundDelivery->clerckName;

        $this->log->LogDebug("refund Delivery: ".$headerId." , ".$refundStatus." , ".$reasonCode." ,  ".$refundNote." , ".$clerkName);

        $helper = new ClickCollectHelper();
        if ($refundStatus=='TO_BE_REFUND') {
            $helper->addCustomAttribute($headerId, $clerkName, 'STATUS',$refundStatus);
            $helper->updateMagentoStoreOrderStatus($headerId, $refundStatus);
            if ($reasonCode)
                $helper->addCustomAttribute($headerId, $clerkName, 'reasonCode',$reasonCode);
            if ($refundNote)
                $helper->addCustomAttribute($headerId, $clerkName, 'refundNote',$refundNote);

        }

        if ($refundStatus=='REFUND') {
            $helper->addCustomAttribute($headerId, $clerkName, 'STATUS',$refundStatus);
            $helper->updateMagentoStoreOrderStatus($headerId, $refundStatus);
            if ($reasonCode)
                $helper->addCustomAttribute($headerId, $clerkName, 'reasonCode',$reasonCode);
            if ($refundNote)
                $helper->addCustomAttribute($headerId, $clerkName, 'refundNote',$refundNote);
        }

        $obj = new stdClass();
        $obj->status="OK";

        $ret = new stdClass();
        $ret->refund = $obj;

        return $ret;

    }

}

/*
$filtro = new stdClass();
$filtro->storeCodePick="ALL";
$filtro->orderNumber='00158959';
$filtro->orderStatus=null;
$filtro->dateFrom=null;
$filtro->dateTo=null;

$t = new DeliveryServiceHelper();
$t->getListaOrdini($filtro);
*/