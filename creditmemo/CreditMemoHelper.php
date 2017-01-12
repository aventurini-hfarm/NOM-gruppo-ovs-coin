<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 12/07/15
 * Time: 11:50
 */
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');

require_once realpath(dirname(__FILE__))."/../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../common/OMDBManager.php";
require_once realpath(dirname(__FILE__))."/../paymentgw/PaymentProcessor.php";
require_once realpath(dirname(__FILE__))."/../omdb/CountryDBHelper.php";

Mage::app();


class CreditMemoHelper {

    private $log;

    public function __construct()
    {

        $this->log = new KLogger('/var/log/nom/creditmemo_helper.log',KLogger::DEBUG);
    }

    public function process($start_date = null, $end_date=null, $generator = false) {
        if (!$start_date) $start_date = Date('Y-m-d 00:00:00');
        if (!$end_date) $end_date = Date('Y-m-d 23:59:59');

        $this->log->LogDebug("Partenza: ".$start_date." , End: ".$end_date);
        //$start_date = "2015-09-16 00:00:00";
        //$end_date = "2015-09-16 23:59:59";
        $this->log->LogDebug('Estrae lista credit memo da esportare');
        $lista = $this->getListaCreditMemoExportare($start_date, $end_date, $generator);
        //print_r($lista);
        //crea la parte fiscale ovvero le numerazioni
        foreach ($lista as $item )
            $this->createFiscalInfo($item);

        $this->addToDB($lista);

        return $lista;

    }

    public function processManualList($lista) {
        foreach ($lista as $item )
            $this->createFiscalInfo($item);

    }

    public function doRefund($lista) {
        foreach ($lista as $item) {
            $order_id = $item->order_id;
            $creditmemo_id = $item->creditmemo_id;

            $creditmemo_details = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);
            $order = Mage::getModel('sales/order')->load($order_id);
            $dw_order_number = $order->getDwOrderNumber();
            $this->log->LogInfo('Request Refund for: '.$dw_order_number.", amount: ".$creditmemo_details->grand_total);
            $payment = new PaymentProcessor($dw_order_number);
            $payment->doRefund($creditmemo_details->grand_total);
        }
    }

    public function doLoyaltyPointsAdj($lista) {
        foreach ($lista as $item) {
            $order_id = $item->order_id;
            $creditmemo_id = $item->creditmemo_id;

            $creditmemo_details = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);

            $order = Mage::getModel('sales/order')->load($order_id);
            $numero_ordine = $order->getDwOrderNumber();
            $variazione_punti = $creditmemo_details->reward_points_balance;

            $this->log->LogInfo('Request Variazione punti fedeltà: '.$numero_ordine.", amount: ".$variazione_punti);


            $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());

            $order_custom_attributes = $orderDBHelper->getCustomAttributes();

            $carta_loyalty='';
            if ($order_custom_attributes['loyaltyCard']) {
                $carta_loyalty = $order_custom_attributes['loyaltyCard'];

                $codice_ecommerce= 4563;
                $soapClient = new Services();

                $timestamp = date('Y-m-d H:m:s');
                $param = new AdjustmentRequest($codice_ecommerce, $carta_loyalty, $numero_ordine, $variazione_punti,0,0, $timestamp);
                $p1 = new Adjustment($param);
                $response = $soapClient->Adjustment($p1);
            }
            return;

        }
    }

    private function addToDB($lista) {
        $con = OMDBManager::getConnection();
        foreach ($lista as $item) {
            $creditmemo_id= $item->creditmemo_id;
            $order_id = $item->order_id;
            $sql ="INSERT INTO conferma_creditmemo (creditmemo_id, order_id)
            VALUES ($creditmemo_id, $order_id)";
            //echo "\nSQL: ".$sql;

            $res =mysql_query($sql);
        }

        OMDBManager::closeConnection($con);
    }

    private function createFiscalInfo($item) {

        $order_id = $item->order_id;
        $creditmemo_id = $item->creditmemo_id;

        $info = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);

        $order = Mage::getModel('sales/order')->load($order_id);


        //genera scontrino sempre se non giù generato
        if (!$info->bill_number) {
            $num_scontrino = CountersHelper::getTrxHeaderId();
            $data_documento = date('d/m/Y');
            $con = OMDBManager::getMagentoConnection();
            $sql = "UPDATE sales_flat_creditmemo SET bill_number='$num_scontrino', bill_date='$data_documento' WHERE
            entity_id = $creditmemo_id";
            $res = mysql_query($sql);
            OMDBManager::closeConnection($con);
            if (!$res) {
                $this->log->LogError('Errore sql aggiornamento info fiscali: '.$sql);
            }
            $this->log->LogDebug("Aggiorna fiscal info: ".$order_id." , ".$creditmemo_id.", nums:".$num_scontrino.", ds: ".$data_documento);
        } else {
            $this->log->LogDebug("Aggiornamento fiscale non necessario: ".$order_id." , ".$creditmemo_id.", nums:".$info->bill_number.", ds: ".$info->bill_date);
        }

        $billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());                       // RINO 31/07/2016
        $billing_country = $billingAddress->getData('country_id');                                                          // RINO 31/07/2016
        $country_details = CountryDBHelper::getCountryDetails($billing_country);                                            // RINO 31/07/2016

        //if ($order->getData('needInvoice')=='true' && !$info->invoice_number) {                                           // RINO 31/07/2016
        if ( ($order->getData('needInvoice')=='true' || $country_details->sopra_soglia == '1') && !$info->invoice_number) { // RINO 31/07/2016
            //genera fattura solo se richiesto
            //$num_fattura= str_pad(CountersHelper::getInvoiceNumber(date('Y')), 4,'0', STR_PAD_LEFT);                      // RINO 31/07/2016
            //$country = $country_details->sopra_soglia=='1' ? $billing_country : 'it';
            $codice_ente = $country_details->codice_ente;
                                                   // RINO 31/07/2016
            //$num_fattura=$numero_doc = str_pad(CountersHelper::getInvoiceNumber(date('Y'), $country), 7,'0', STR_PAD_LEFT); // RINO 31/07/2016
            $num_fattura=$numero_doc = str_pad(CountersHelper::getInvoiceNumber(date('Y'), $codice_ente), 7,'0', STR_PAD_LEFT); // RINO 31/07/2016

            $data_documento = date('d/m/Y');
            $con = OMDBManager::getMagentoConnection();
            $sql = "UPDATE sales_flat_creditmemo SET invoice_number='$num_fattura', invoice_date='$data_documento' WHERE
        entity_id = $creditmemo_id";
            $res = mysql_query($sql);
            OMDBManager::closeConnection($con);
            if (!$res) {
                $this->log->LogError('Errore sql aggiornamento info fiscali (fattura): '.$sql);
            }

            $this->log->LogDebug("Aggiorna fiscal info Fattura: ".$order_id." , ".$creditmemo_id.", nums:".$num_scontrino.
                ", numf: ".$num_fattura." , df:".$data_documento);
        } else {
            $this->log->LogDebug("Aggiornamento fiscale fattura non necessario: ".$order_id." , ".$creditmemo_id.", numf:".$info->invoice_number.", df: ".$info->invoice_date);
        }



    }

    public function getListaCreditMemoExportare($start = null, $end = null, $generator = false) {

        $con = OMDBManager::getMagentoConnection();

        if ($generator)
            $sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE (created_at BETWEEN '$start' AND '$end') and bill_date=''";
        else
            $sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE bill_date = '$start'";

        echo "\n".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $obj = new stdClass();
            $obj->creditmemo_id = $row->entity_id;
            $obj->order_id = $row->order_id;
            $this->log->LogDebug("Lista Credit Memo da processare: c_id".$row->entity_id." , o_id".$row->order_id);
            $lista[] = $obj;
        }
        OMDBManager::closeConnection($con);

        $this->log->LogDebug("Note di credito.Record trovati:");
        print_r($lista);
        return $lista;
    }



    public static function getCreditMemoItems($entity_id) {
        $items = array();

        // { RINO 23/09/2016
        $creditMemo = Mage::getModel('sales/order_creditmemo')->load($entity_id);

        $order = Mage::getModel('sales/order')->load($creditMemo->getOrderId());

        $country_id = strtoupper(substr($order->getCustomerLocale(),0,2));

        $con = OMDBManager::getConnection();
        // } RINO 23/09/2016

        $creditItem = Mage::getResourceModel('sales/order_creditmemo_item_collection')
            ->addAttributeToFilter('parent_id', $entity_id);



        foreach ($creditItem as $item) {
            $obj = new stdClass();
            $obj->entity_id = $item->entity_id;
            $obj->base_price = $item->base_price;
            $obj->base_row_total = $item->base_row_total;
            $obj->row_total = $item->row_total;
            $obj->qty = $item->qty;
            $obj->price = $item->price;
            $obj->product_id = $item->product_id;
            $obj->sku = $item->sku;
            //$obj->name = $item->name;
            $obj->discount_value = $item->discount_amount;
            // {  RINO 23/09/2016
            $sql ="SELECT baseName$country_id from  estero_catalog where entity_id=$item->product_id";
            $res = mysql_query($sql);
            $row = mysql_fetch_array($res);
            if ($row)
                $obj->name = $row[0];
            else
                $obj->name = $item->name;
            // } RINO 23/09/2016
            $obj->tax_amount = $item->tax_amount;   //RINO 07/09/2016
            $items[] = $obj;
        }

        //print_r($creditItem->getData());
        //print_r($items);
        OMDBManager::closeConnection($con);

        return $items;
    }

    public static function getCreditMemoDetails($entity_id) {

        $con = OMDBManager::getMagentoConnection();
        $sql = "SELECT * FROM sales_flat_creditmemo WHERE entity_id=$entity_id";
        //echo "\ngetCreditMemoDetails SQL : ".$sql;

        $res = mysql_query($sql);
        while ($row=mysql_fetch_object($res)) {
            $obj = new stdClass();

            $obj->grand_total = $row->grand_total;
            $obj->base_subtotal = $row->base_subtotal;
            $obj->subtotal = $row->subtotal;
            $obj->base_grand_total = $row->base_grand_total;
            $obj->shipping_amount = $row->shipping_amount;          //RINO 31/07/2016
            //$obj->shipping_amount = $row->base_shipping_incl_tax;     //RINO 31/07/2016
            $obj->shipping_amount_orig = $row->shipping_amount;     //RINO 02/09/2016
            $obj->discount_value = $row->discount_amount;
            $obj->discount_description = $row->discount_description;
            $obj->created_at = $row->created_at;
            $obj->bill_number = $row->bill_number;
            $obj->bill_date = $row->bill_date;
            $obj->invoice_number = $row->invoice_number;
            $obj->invoice_date = $row->invoice_date;
            $obj->reward_points_balance = $row->reward_points_balance;
            $obj->increment_id = $row->increment_id;

            // RINO 31/07/2016
            $order=Mage::getModel('sales/order')->load($row->order_id);
            $obj->rif_invoice_number = $order->getInvoiceNumber();
            $obj->rif_invoice_date = $order->getInvoiceDate();


        }

        OMDBManager::closeConnection($con);
        //print_r ($obj);
        return $obj;
    }
}

//CreditMemoHelper::getCreditMemoDetails(6);
//$t = new CreditMemoHelper();
//$t->getListaCreditMemoExportare("2015-07-12 00:00:00",'2015-07-12 23:59:59');

//$t->getCreditMemoItems(6);
//$t->process();