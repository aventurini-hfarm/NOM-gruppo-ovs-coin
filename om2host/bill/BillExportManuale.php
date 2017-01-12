<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:33
 */

require_once realpath(dirname(__FILE__))."/../../common/OMDBManager.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/CountersHelper.php";
require_once realpath(dirname(__FILE__))."/../../common/FileGenerator.php";
require_once realpath(dirname(__FILE__))."/RegisterOpenRecord.php";
require_once realpath(dirname(__FILE__))."/ItemFeeRecord.php";
require_once realpath(dirname(__FILE__))."/ItemStockRecord.php";
require_once realpath(dirname(__FILE__))."/ItemTenderRecord.php";
require_once realpath(dirname(__FILE__))."/RegisterCloseRecord.php";
require_once realpath(dirname(__FILE__))."/TrxDiscountRecord.php";
require_once realpath(dirname(__FILE__))."/TrxHeaderRecord.php";
require_once realpath(dirname(__FILE__))."/../../omdb/OrderDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../omdb/PaymentDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../dw2om/orders/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__)) . "/../../omdb/PaymentDBHelper.php";
require_once realpath(dirname(__FILE__)) . "/../../creditmemo/CreditMemoHelper.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();


class BillExport {

    private $status_to_export = "complete";

    private $log;
    private $config;
    private $DIRECTORY_OUTPUT="/tmp";

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/bill_export.log',KLogger::DEBUG);

    }

    /**
     * Inizia export flusso scontrini in base al range temporale
     * @param $start data inizio
     * @param $end data fine
     */
    public function export($lista_dw_order_no, $lista_creditmemo) {

        $this->log->LogInfo("Start");
        $lista_ordini = $this->getListaOrdiniDaExportare($lista_dw_order_no);
        //$lista_creditmemo = $this->getListaCreditMemoDaExportare($start, $end);

        if ($lista_ordini || $lista_creditmemo) {
            $records = $this->generateBillExport($lista_ordini, $lista_creditmemo);

            $this->writeRecordToFile($records);
        }
        else
            $this->log->LogInfo("\nNessun ordine da esportare");

    }

    private function generateBillExport($lista_ordini, $lista_creditmemo) {
        $lista_record = array();
        $start_date = date('d/m/Y H:i:s');
        $record = new RegisterOpenRecord($this->config->getEcommerceShopCode(), $start_date);
        array_push($lista_record, $record);
        $codice_cassa = $this->config->getEcommerceShopCodiceCassa();


        foreach ($lista_ordini as $increment_id) {
            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
            $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
            $order_custom_attributes = $orderDBHelper->getCustomAttributes();

            $manager = new PaymentDBHelper($order->getDwOrderNumber());
            $paymentObj = $manager->getPaymentInfo();


            //$trx_header_id = CountersHelper::getTrxHeaderId();
            $trx_header_id = $order->getData('bill_number'); //La leggo dall'ordine visto che il flusso bill viene generato dopo che l'ordine è messo in complete
            $tipo_transazione = "V";

            $tessera_fidelity='';
            if ($order_custom_attributes['loyaltyCard'])
                $tessera_fidelity = str_pad($order_custom_attributes['loyaltyCard'], 9, '0',STR_PAD_LEFT);

            $punti_guadagnati = $order_custom_attributes['rewardPoints'];
            $punti_spesi = $order_custom_attributes['spentPoints'];
            $tmp = $order->getBillingAddress()->getData();
            $cap = $tmp['postcode'];


            //print_r($order->getData());
            $valuta = "EUR";
            $codice_cliente = $order->getCustomerId();

            //inizio - Modifica per codice cliente per mettere lo stesso di SG
            $customerTmpHelper = Mage::getModel('customer/customer');
            $customerTmp = $customerTmpHelper->load($order->getCustomerId());
            $sg_user_id = $customerTmp->getData('sg_user_id');
            if (!$sg_user_id) $sg_user_id = $codice_cliente;
            $codice_cliente = $sg_user_id;
            //- fine Modifica per codice cliente per mettere lo stesso di SG


            $codice_cliente_dw = $order->getDwCustomerId();
            $order_no = $order->getDwOrderNumber();
            $data_ordine = $order->getCreatedAt();
            $newDate_ordine = date("d/m/Y H:i:s", strtotime($order->getDwOrderDatetime()));

            $trx_date = $paymentObj->timestamp;

            /* FIX 05112015*/
            /* per CO perchè la data non esiste */

            $payment = $order->getPayment();
            $payment_method_selected = $payment->getMethod();
            if ($payment_method_selected=='cashondelivery') {
                //$trx_date = date('Y-m-d');
                //$trx_date = date('d/m/Y',strtotime($order->getData('bill_date')));
                $trx_date = date_format(date_create_from_format('d/m/Y', $order->getData('bill_date')), 'Y-m-d');

                //$trx_date = '2015-11-04';
            }
            /* END FIX per CO*/

            $esenziona_iva = '0'; //richiesta da Zennaro il 30/06/2015
            $newDate_trx_date = date("d/m/Y H:i:s", strtotime($trx_date));
            $trx_header = new TrxHeaderRecord($codice_cassa, $trx_header_id, $newDate_trx_date, $tipo_transazione, $tessera_fidelity,
            $punti_guadagnati, $punti_spesi, $cap, $valuta, $codice_cliente, $codice_cliente_dw,
            $order_no, $newDate_ordine, $esenziona_iva);
            array_push($lista_record, $trx_header);

            //TRX DISCOUNT
            $orderDbHelper = new OrderDBHelper($order_no);
            $promoObjArray = $orderDbHelper->getMerchandizePromotion();



            foreach ($promoObjArray as $promoObj) {

                $valore_promo = number_format($promoObj->value * -1, 2);
                $valore = str_pad(str_replace('.','', $valore_promo ),7,'0',STR_PAD_LEFT);
                $valore = str_pad(str_replace(',','', $valore ),7,'0',STR_PAD_LEFT);
                $trx_discount = new TrxDiscountRecord($valore, $promoObj->promotion_id);
                array_push($lista_record, $trx_discount);
            }

            //ITEM_STOCK
            $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
            foreach ($lines as $line) {
                //print_r($line);
                $sku = $line['sku'];
                $qty = $line['order_quantity'];
/*
                if ($order_no=='00162259') {
                    $line['discount_value']=0;
                    $line['item_dw_promo_id']='';
                }
*/
                $unit_price = str_pad(str_replace('.','', $line['unit_price']),7,'0',STR_PAD_LEFT);
                $unit_price = str_pad(str_replace(',','', $unit_price),7,'0',STR_PAD_LEFT);
                $discount_value = str_pad(str_replace('.','', $line['discount_value']),7,'0',STR_PAD_LEFT);
                $discount_value = str_pad(str_replace(',','', $discount_value),7,'0',STR_PAD_LEFT);
                $item_dw_promo_id = $line['item_dw_promo_id'];
                $item_dw_extra_points = $line['item_dw_extra_points'];
                $item_dw_return_points = $line['item_dw_return_points'];

                $stockRecord = new ItemStockRecord($sku, $unit_price, $qty, $discount_value, $item_dw_extra_points, $item_dw_return_points, $item_dw_promo_id);
                array_push($lista_record, $stockRecord);

            }


            //ITEM_FEE altri opzioni (montaggio)
            foreach ($lines as $line) {
                $item_has_options = $line['item_has_options'];
                print_r($line);
                if ($item_has_options=='1') {

                    $options = $orderDbHelper->getItemOptions($line['sku']);
                    print_r($options);

                    foreach ($options as $option) {
                        if ( ($option->option_key=='product-id')  && ($option->option_value=='Montaggio') ){
                            $valore = $options['base-price']->option_value;
                            $valoreMontaggio = str_pad(str_replace('.','', $valore),7,'0',STR_PAD_LEFT);
                            $valoreScontoMontaggio = str_pad(str_replace('.','', '0.00'),7,'0',STR_PAD_LEFT);


                            $item_fee = new ItemFeeRecord('Montaggio', $valoreMontaggio, $valoreScontoMontaggio, '');
                            array_push($lista_record, $item_fee);
                        }
                    }

                }
            }


            //ITEM_FEE Spedizione
            $promoObjArray = $orderDbHelper->getShippingPromotion();
            print_r($promoObj);
            $shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);
            $shippingAmount_fmt = str_pad(str_replace('.','', $shippingAmount),7,'0',STR_PAD_LEFT);
            $shippingAmount_fmt = str_pad(str_replace(',','', $shippingAmount_fmt),7,'0',STR_PAD_LEFT);

            foreach ($promoObjArray as $promoObj) {
                $shippingDiscount = number_format(($promoObj->value * -1),2);
                $shippingDiscount_fmt = str_pad(str_replace('.','', $shippingDiscount),7,'0',STR_PAD_LEFT);
                $shippingDiscount_fmt = str_pad(str_replace(',','', $shippingDiscount_fmt),7,'0',STR_PAD_LEFT);
                $trx_discount = new ItemFeeRecord('Shipping Charges', $shippingAmount_fmt, $shippingDiscount_fmt, $promoObj->promotion_id);
                array_push($lista_record, $trx_discount);
            }

            //ITEM_TENDER
            $payment = $order->getPayment();
            $payment_method_selected = $payment->getMethod();
            //print_r($payment->getData());
            //echo "\nPayment Method: ".$payment_method_selected;
            $orderValue = number_format($order->getBaseGrandTotal(),2);
            $orderValue_fmt = str_pad(str_replace('.','', $orderValue),7,'0',STR_PAD_LEFT);
            $orderValue_fmt = str_pad(str_replace(',','', $orderValue_fmt),7,'0',STR_PAD_LEFT); //18012016
            if ($payment_method_selected=='ccsave') {
                $txr_tender = new ItemTenderRecord('CC',$payment->getCcType(), $orderValue_fmt);
            }elseif ($payment_method_selected=='cashondelivery') {
                $txr_tender = new ItemTenderRecord('CO','', $orderValue_fmt);
            }
            else {
                //PayPal
                $txr_tender = new ItemTenderRecord('PP','',$orderValue_fmt );
            }

            array_push($lista_record, $txr_tender);

        } //end for



        /**
         * SEZIONE CREDITMEMO
         */

        echo "\nPrepara CreditMemo";

        foreach ($lista_creditmemo as $obj) {
            $order_id = $obj->order_id;
            $creditmemo_id = $obj->creditmemo_id;
            $info_creditmemo = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);

            $order = Mage::getModel('sales/order')->load($order_id);
            $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
            $order_custom_attributes = $orderDBHelper->getCustomAttributes();

            $manager = new PaymentDBHelper($order->getDwOrderNumber());
            $paymentObj = $manager->getPaymentInfo();


            //$trx_header_id = CountersHelper::getTrxHeaderId();
            $trx_header_id = $info_creditmemo->bill_number;
            $tipo_transazione = "R";

            $tessera_fidelity='';
            if ($order_custom_attributes['loyaltyCard'])
                $tessera_fidelity = str_pad($order_custom_attributes['loyaltyCard'], 9, '0',STR_PAD_LEFT);

            $punti_guadagnati = $order_custom_attributes['rewardPoints'];
            $punti_spesi = $order_custom_attributes['spentPoints'];
            $tmp = $order->getBillingAddress()->getData();
            $cap = $tmp['postcode'];


            //print_r($order->getData());
            $valuta = "EUR";
            $codice_cliente = $order->getCustomerId();

            //inizio - Modifica per codice cliente per mettere lo stesso di SG
            $customerTmpHelper = Mage::getModel('customer/customer');
            $customerTmp = $customerTmpHelper->load($order->getCustomerId());
            $sg_user_id = $customerTmp->getData('sg_user_id');
            if (!$sg_user_id) $sg_user_id = $codice_cliente;
            $codice_cliente = $sg_user_id;
            //- fine Modifica per codice cliente per mettere lo stesso di SG

            $codice_cliente_dw = $order->getDwCustomerId();
            $order_no = $order->getDwOrderNumber();
            $data_ordine = $order->getCreatedAt();
            $newDate_ordine = date("d/m/Y H:i:s", strtotime($order->getDwOrderDatetime()));

            //$trx_date = $paymentObj->timestamp;
            //$trx_date = date('d/m/Y H:i:s',strtotime($info_creditmemo->bill_date));
//ALESSIO
			$trx_date = date_format(date_create_from_format('d/m/Y', $info_creditmemo->bill_date), 'Y-m-d');//In realtà per le note di credito occorre prendere la data dello scontrino di nota di credito	
		    //var_dump(substr($info_creditmemo->created_at,0,10));	// prendo la data da created_at (che è YYYY-mm-dd hh:mm:ss
			$trx_date = /*substr(*/$info_creditmemo->created_at;//,0,10);		//e la metto come trx_date
		    var_dump($trx_date);
//exit;		
            /* FIX 05112015*/
            /* per CO perchè la data non esiste */

            $payment = $order->getPayment();
            $payment_method_selected = $payment->getMethod();

            //if ($payment_method_selected=='cashondelivery') {
                //$trx_date = date('Y-m-d');
            //    $trx_date = date('d/m/Y',strtotime($order->getData('bill_date')));
            //}
            /* END FIX per CO*/


            $esenziona_iva = '0'; //richiesta da Zennaro il 30/06/2015
            $newDate_trx_date = date("d/m/Y H:i:s", strtotime($trx_date));
            $trx_header = new TrxHeaderRecord($codice_cassa, $trx_header_id, $newDate_trx_date, $tipo_transazione, $tessera_fidelity,
                $punti_guadagnati, $punti_spesi, $cap, $valuta, $codice_cliente, $codice_cliente_dw,
                $order_no, $newDate_ordine, $esenziona_iva);
            array_push($lista_record, $trx_header);

            //TRX DISCOUNT
            /* 23122015 i DISCOUNT NON VANNO MESSI*/
            /*
            $orderDbHelper = new OrderDBHelper($order_no);
            $promoObjArray = $orderDbHelper->getMerchandizePromotion();


            foreach ($promoObjArray as $promoObj) {

                //$valore_promo = number_format($promoObj->value * -1, 2);
                $valore_promo = number_format($promoObj->value , 2); //26102015 -> valore positivo secondo Zennaro
                $valore = str_pad(str_replace('.','', $valore_promo ),7,'0',STR_PAD_LEFT);
                $trx_discount = new TrxDiscountRecord($valore, $promoObj->promotion_id);
                array_push($lista_record, $trx_discount);
            }
            */

            //ITEM_STOCK
            //$lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
            $lines = CreditMemoHelper::getCreditMemoItems($creditmemo_id);

            foreach ($lines as $line) {
                //print_r($line);
                $sku = $line->sku;
                //$qty = -1 * $line->qty;
                $qty =  1 * $line->qty; //26102015 -> valore positivo secondo Zennaro
                $bp = number_format($line->base_price,2);
                $dv = number_format($line->discount_value,2);
                $unit_price = str_pad(str_replace('.','', $bp),7,'0',STR_PAD_LEFT);
                $unit_price = str_pad(str_replace(',','', $unit_price),7,'0',STR_PAD_LEFT);
                $discount_value = str_pad(str_replace('.','', $dv),7,'0',STR_PAD_LEFT);
                $discount_value = str_pad(str_replace(',','', $discount_value),7,'0',STR_PAD_LEFT);

                $item_dw_promo_id = '';
                $item_dw_extra_points = '';
                $item_dw_return_points = '';

                $stockRecord = new ItemStockRecord($sku, $unit_price, $qty, $discount_value, $item_dw_extra_points, $item_dw_return_points, $item_dw_promo_id);
                array_push($lista_record, $stockRecord);

            }



            //ITEM_FEE Spedizione la prende dalla nota di credito. Se c'è va quindi riaccreditato il trasporto

            $crdetails = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);
            //$shipping_amount = $crdetails->shipping_amount * -1;
            $shipping_amount = $crdetails->shipping_amount; //26102015 -> valore positivo secondo Zennaro
            echo "\nShipping_amount_credit: ".$shipping_amount;
            $shippingAmount = number_format($shipping_amount,2);
            $shippingAmount_fmt = str_pad(str_replace('.','', $shippingAmount),7,'0',STR_PAD_LEFT);

                $shippingDiscount = number_format(0,2);
                $shippingDiscount_fmt = str_pad(str_replace('.','', $shippingDiscount),7,'0',STR_PAD_LEFT);
                $trx_discount = new ItemFeeRecord('Shipping Charges', $shippingAmount_fmt, $shippingDiscount_fmt, '');
                array_push($lista_record, $trx_discount);

            //ITEM_TENDER
            $payment = $order->getPayment();
            $payment_method_selected = $payment->getMethod();
            //print_r($payment->getData());
            //echo "\nPayment Method: ".$payment_method_selected;
            $orderValue = number_format($info_creditmemo->grand_total,2);
            //$orderValue_fmt = "-".str_pad(str_replace('.','', $orderValue),7,'0',STR_PAD_LEFT);
            $orderValue_fmt = str_pad(str_replace('.','', $orderValue),7,'0',STR_PAD_LEFT); //26102015 -> valore positivo secondo Zennaro
            $orderValue_fmt = str_pad(str_replace(',','', $orderValue_fmt),7,'0',STR_PAD_LEFT); //26102015 -> valore positivo secondo Zennaro
            if ($payment_method_selected=='ccsave') {
                $txr_tender = new ItemTenderRecord('CC',$payment->getCcType(), $orderValue_fmt);
            }elseif ($payment_method_selected=='cashondelivery') {
                $txr_tender = new ItemTenderRecord('CO','', $orderValue_fmt);
            }
            else {
                //PayPal
                $txr_tender = new ItemTenderRecord('PP','',$orderValue_fmt );
            }

            array_push($lista_record, $txr_tender);

        } //end for CREDIT MEMO

        //REGISTER_CLOSE
        $end_date = date('d/m/Y H:i:s');
        $record = new RegisterCloseRecord($end_date);
        array_push($lista_record, $record);

        //scrive i record
        return $lista_record;
    }

    private function writeRecordToFile($lista_record) {

        $content = array();
        foreach ($lista_record as $record) {
            echo "\n".$record->getLine();
            $content[] = $record->getLine();
        }

        $timestamp = date('Ymdhis');
        $codiceShop = $this->config->getEcommerceShopCode();
        $file_name = "ESL_".$codiceShop."_SALES_".$timestamp.".TXT";
        $directory = $this->config->getBillExportOutboundDir();
        //$full_name = $directory."/".$file_name;
        $full_name = $this->DIRECTORY_OUTPUT."/".$file_name;

        $fileGenerator = new FileGenerator();
        $fileGenerator->createFile($full_name);

        $fileGenerator->writeRecord($content);
        $fileGenerator->closeFile();

        unset($content);
    }

    /**
     * Estrae la lista ordini direttamente da magento
     * @param null $start
     * @param null $end
     * @return mixed
     */
    private function getListaOrdiniDaExportare($lista_dw_order_number) {

        $con = OMDBManager::getMagentoConnection();

        //$sql ="SELECT increment_id FROM sales_flat_order WHERE (created_at BETWEEN '$start' AND '$end')
        // AND status='$status'"; //nel flusso scontrini ci vanno anche quelli che hanno chiesto fattura

        $lista = array();
        foreach ($lista_dw_order_number as $dw_order_number) {
            $sql ="SELECT increment_id FROM sales_flat_order WHERE dw_order_number='$dw_order_number'";

            //echo "\nLog: ".$sql;
            $res = mysql_query($sql);

            while ($row = mysql_fetch_object($res)) {
                $this->log->LogDebug("Record trovati:".$row->increment_id);
                $lista[] = $row->increment_id;
                echo "\nFound: ".$lista_dw_order_number." , ".$row->increment_id;
            }
        }
        OMDBManager::closeConnection($con);

        return $lista;
    }

    public function _getListaCreditMemoDaExportare($start = null, $end = null) {

        $t = new CreditMemoHelper();
        $lista = $t->getListaCreditMemoExportare($start, $end);

        return $lista;
    }

    public function getListaCreditMemoDaExportare($start = null, $end = null) {

        $con = OMDBManager::getMagentoConnection();


        $sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE bill_date = '$start'";


        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $obj = new stdClass();
            $obj->creditmemo_id = $row->entity_id;
            //if ($row->entity_id!=72) continue;
            $obj->order_id = $row->order_id;
            $this->log->LogDebug("Lista Credit Memo da processare: c_id".$row->entity_id." , o_id".$row->order_id);
            $lista[] = $obj;
        }
        OMDBManager::closeConnection($con);

        return $lista;

    }



}

//TODO METTERE LA DATA AUTOMATICA
$t = new BillExport();
//$lista_id_ordine = array('00224667');
$lista_id_ordine = array();

$lista_bill_number=array();

foreach ($lista_bill_number as $bill_number) {
    $dw_order_number = BillHelper::getDWOrderNumberByBillNumber($bill_number);
    $lista_id_ordine[] = $dw_order_number;
}
print_r($lista_id_ordine);

$lista_creditmemo_id = array();

//$t->export($lista_id_ordine, $lista_creditmemo_id);
$start="01/0/2016";
$end = "06/04/2016";
$lista_creditmemo_id = $t->getListaCreditMemoDaExportare($start, $end);

print_r($lista_creditmemo_id);
$t->export($lista_id_ordine, $lista_creditmemo_id);