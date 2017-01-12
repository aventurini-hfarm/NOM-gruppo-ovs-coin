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
    /*public function export($start, $end, $startcm, $endcm) {

        $this->log->LogInfo("Start");
        $lista_ordini = $this->getListaOrdiniDaExportare( $start, $end, $this->status_to_export);
        $lista_creditmemo = $this->getListaCreditMemoDaExportare($startcm, $endcm);
        //$lista_creditmemo = array(); //TODO rimuovere domani 29/10/2015
        if ($lista_ordini || $lista_creditmemo) {
            $records = $this->generateBillExport($this->config->getEcommerceShopCode(),$lista_ordini, $lista_creditmemo);

            $this->writeRecordToFile($records);
        }
        else
            $this->log->LogInfo("\nNessun ordine da esportare");

    }*/

    public function exportPerCountry($start, $end, $startcm, $endcm) { // RINO 9/7/2016

        $this->log->LogInfo("Start");
        $lista_ordini = $this->getListaOrdiniDaExportare( $start, $end, $this->status_to_export);
        $lista_creditmemo = $this->getListaCreditMemoDaExportare($startcm, $endcm);

        $all_records= array();
        if ($lista_ordini || $lista_creditmemo) {


            $country_base = CountryDBHelper::getCountryDetails("IT"); //RINO 9/7/2016 per primo country base per ovs è 3737 italia
            $records = $this->generateBillExport($country_base,$lista_ordini, $lista_creditmemo,'0');
            foreach ($records AS $record ) array_push($all_records,$record);

            $countries=CountryDBHelper::getCountries();
            foreach ($countries as $country) {  // RINO 9/7/2016 poi tutti gli altri paesi in gestione sopra soglia

                if ($country->codice_ente != $country_base->codice_ente && $country->sopra_soglia=='1') {
                    $records = $this->generateBillExport($country,$lista_ordini, $lista_creditmemo,'1');
                    foreach ($records AS $record ) array_push($all_records,$record);
                }
            }

            $this->writeRecordToFile($all_records);
        }
        else
            $this->log->LogInfo("\nNessun ordine da esportare");

    }

    public function exportPerEnte($start, $end, $startcm, $endcm) { // RINO 9/7/2016

        $this->log->LogInfo("Start");
        $lista_ordini = $this->getListaOrdiniDaExportare( $start, $end, $this->status_to_export);
        $lista_creditmemo = $this->getListaCreditMemoDaExportare($startcm, $endcm);

        $all_records= array();
        if ($lista_ordini || $lista_creditmemo) {


            $enti=CountryDBHelper::getEnti();
            foreach ($enti as $ente) {
                $records = $this->generateBillExport($ente,$lista_ordini, $lista_creditmemo,'1');
                foreach ($records AS $record )
                    array_push($all_records,$record);
            }

            $this->writeRecordToFile($all_records);
        }
        else
            $this->log->LogInfo("\nNessun ordine da esportare");

    }

    private function generateBillExport($ente, $lista_ordini, $lista_creditmemo, $sopra_soglia) {

        $codice_ente = $ente->codice_ente;

        $lista_record = array();
        $start_date = date('d/m/Y H:i:s');
        $record = new RegisterOpenRecord($codice_ente, $start_date);
        array_push($lista_record, $record);
        $codice_cassa = $this->config->getEcommerceShopCodiceCassa();

        $totale_globale=0;
        $totale_scontrini=0;
        foreach ($lista_ordini as $increment_id) {

            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');

            $billingAddress = $order->getBillingAddress();                                  //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $billingAddressCountryid = strtolower($billingAddress->getCountryId());         //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $orderCountry = CountryDBHelper::getCountryDetails($billingAddressCountryid);   //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $iva = $orderCountry->iva;                                                      //Rino 19/09/2016

            $totale_controllo = 0;
            //if ( ($billingAddressCountryid == $country->country_id && $sopra_soglia=='1')  ||  ( $orderCountry->sopra_soglia == '0' && $sopra_soglia=='0') ) {// RINO 9/7/2016 Se l'ordine  è del paese corrente o è in sotto-soglia ($orderCountry->sopra_soglia == '0' è possibile solo se il $country->country_id è IT tutti gli a tri casi sono stati esclusi a monte
            if ($orderCountry->codice_ente == $codice_ente) {  // rino 19/09/2016
                $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
                $order_custom_attributes = $orderDBHelper->getCustomAttributes();

                $manager = new PaymentDBHelper($order->getDwOrderNumber());
                $paymentObj = $manager->getPaymentInfo();


                //$trx_header_id = CountersHelper::getTrxHeaderId();
                $trx_header_id = $order->getData('bill_number'); //La leggo dall'ordine visto che il flusso bill viene generato dopo che l'ordine è messo in complete
                $tipo_transazione = "V";

                $tessera_fidelity = '';
                if ($order_custom_attributes['loyaltyCard'])
                    $tessera_fidelity = str_pad($order_custom_attributes['loyaltyCard'], 9, '0', STR_PAD_LEFT);

                $punti_guadagnati = $order_custom_attributes['rewardPoints'];
                $punti_spesi = $order_custom_attributes['spentPoints'];
                $tmp = $order->getBillingAddress()->getData();
                $cap = $tmp['postcode'];


                //print_r($order->getData());
                $valuta = "EUR";

                //27-10-2016 (guest checkout)
                if ($order->getData('dw_is_authenticated')=='false') {
                    $codice_cliente_dw = $this->config->getFakeUserId($billingAddress->getCountryId());
                    //$codice_cliente_dw = $order->getDwCustomerId();
                    $codice_cliente = '000000';

                } else {
                    //inizio - Modifica per codice cliente per mettere lo stesso di SG
                    $codice_cliente = $order->getCustomerId();
                    $customerTmpHelper = Mage::getModel('customer/customer');
                    $customerTmp = $customerTmpHelper->load($order->getCustomerId());
                    $sg_user_id = $customerTmp->getData('sg_user_id');
                    if (!$sg_user_id) $sg_user_id = $codice_cliente;
                    $codice_cliente = $sg_user_id;
                    $codice_cliente_dw = $order->getDwCustomerId();
                    $codice_cliente=ltrim($codice_cliente,'0'); //RINO 27/07/2016
                }
                //- fine Modifica per codice cliente per mettere lo stesso di SG



                //$codice_cliente_dw = $order->getDwCustomerId();
                $order_no = $order->getDwOrderNumber();
                $data_ordine = $order->getCreatedAt();
                $newDate_ordine = date("d/m/Y H:i:s", strtotime($order->getDwOrderDatetime()));

                $trx_date = $paymentObj->timestamp;

                /* FIX 05112015*/
                /* per CO perchè la data non esiste */

                $payment = $order->getPayment();
                $payment_method_selected = $payment->getMethod();
                if ($payment_method_selected == 'cashondelivery'  || $payment_method_selected == 'free') {  //RINO 10/10/2016 fix ordini di tipo CHIOSCO
                    //$trx_date = date('Y-m-d');
                    //$trx_date = date('d/m/Y',strtotime($order->getData('bill_date')));
                    $trx_date = date_format(date_create_from_format('d/m/Y', $order->getData('bill_date')), 'Y-m-d');
                    //$trx_date = '2015-11-04';
                }
                /* END FIX per CO*/

                $tmp_lista = array();

                $esenziona_iva = '0'; //richiesta da Zennaro il 30/06/2015
                $newDate_trx_date = date("d/m/Y H:i:s", strtotime($trx_date));
                $trx_header = new TrxHeaderRecord($codice_cassa, $trx_header_id, $newDate_trx_date, $tipo_transazione, $tessera_fidelity,
                    $punti_guadagnati, $punti_spesi, $cap, $valuta, $codice_cliente, $codice_cliente_dw,
                    $order_no, $newDate_ordine, $esenziona_iva);
                //array_push($lista_record, $trx_header);
                array_push($tmp_lista, $trx_header);


                //TRX DISCOUNT
                $orderDbHelper = new OrderDBHelper($order_no);
                $promoObjArray = $orderDbHelper->getMerchandizePromotion();

                foreach ($promoObjArray as $promoObj) {

                    $valore_promo = number_format($promoObj->value * -1, 2);
                    $valore = str_pad(str_replace('.','', $valore_promo ),7,'0',STR_PAD_LEFT);
                    $valore = str_pad(str_replace(',','', $valore ),7,'0',STR_PAD_LEFT);
                    $trx_discount = new TrxDiscountRecord($valore, $promoObj->promotion_id);
                    //array_push($lista_record, $trx_discount);

                    array_push($tmp_lista, $trx_discount);

                    $totale_controllo += $promoObj->value;
                    echo "\nTotale_controllo: ".$totale_controllo;

                }

                //ITEM_STOCK
                $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
                foreach ($lines as $line) {
                    //print_r($line);
                    $sku = $line['sku'];
                    $qty = $line['order_quantity'];
                    $unit_price_0 = number_format( $line['unit_price'] ,2);
                    //$unit_price = str_pad(str_replace('.','', $line['unit_price']),7,'0',STR_PAD_LEFT);
                    $unit_price = str_pad(str_replace('.','', $unit_price_0),7,'0',STR_PAD_LEFT);
                    $unit_price = str_pad(str_replace(',','', $unit_price),7,'0',STR_PAD_LEFT);
                    //$tmp_discount = number_format($line['discount_value'] / $qty,2);
                    $tmp_discount = str_pad(str_replace('.','', $line['discount_value']),7,'0',STR_PAD_LEFT);
                    $discount_value = str_pad(str_replace('.','', $tmp_discount),7,'0',STR_PAD_LEFT);
                    $discount_value = str_pad(str_replace(',','', $discount_value),7,'0',STR_PAD_LEFT);
                    $item_dw_promo_id = $line['item_dw_promo_id'];
                    $item_dw_extra_points = $line['item_dw_extra_points'];
                    $item_dw_return_points = $line['item_dw_return_points'];

                    $stockRecord = new ItemStockRecord($sku, $unit_price, $qty, $discount_value, $item_dw_extra_points, $item_dw_return_points, $item_dw_promo_id);
                    //array_push($lista_record, $stockRecord);
                    array_push($tmp_lista, $stockRecord);

                    $totale_controllo += ( ($unit_price_0 * $qty) - $line['discount_value']);
                   // echo "\nTotale_controllo: ".$totale_controllo;

                }


                //ITEM_FEE Spedizione
                $tmp_shipping_amount = $order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1);
                if ($tmp_shipping_amount>0) {//se non ci sono spese non mettere il record ITEM_FEE
                    $promoObjArray = $orderDbHelper->getShippingPromotion();
                    $shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);
                    $shippingAmount_fmt = str_pad(str_replace('.','', $shippingAmount),7,'0',STR_PAD_LEFT);
                    $shippingAmount_fmt = str_pad(str_replace(',','', $shippingAmount_fmt),7,'0',STR_PAD_LEFT);

                    $totale_controllo += $order->getShippingAmount() ;
                    $totale_controllo += $order->getBaseShippingDiscountAmount();

                    foreach ($promoObjArray as $promoObj) {
                        $shippingDiscount = number_format(($promoObj->value * -1),2);
                        $shippingDiscount_fmt = str_pad(str_replace('.','', $shippingDiscount),7,'0',STR_PAD_LEFT);
                        $shippingDiscount_fmt = str_pad(str_replace(',','', $shippingDiscount_fmt),7,'0',STR_PAD_LEFT);
                        $trx_discount = new ItemFeeRecord('Shipping Charges', $shippingAmount_fmt, $shippingDiscount_fmt, $promoObj->promotion_id);
                        //array_push($lista_record, $trx_discount);
                        if ($order->getShippingAmount() + $promoObj->value >0 )
                            array_push($tmp_lista, $trx_discount);

                        $totale_controllo -= $promoObj->value ;
                        //echo "\nTotale_controllo (Shipping Promo): ".$totale_controllo;

                    }
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


                //array_push($lista_record, $txr_tender);
                array_push($tmp_lista, $txr_tender);

                if (round($totale_controllo,2) != round($order->getBaseGrandTotal(),2)) {
                    echo "\nAttenzione squadratura: ".$order->getDwOrderNumber()." totale_controllo=".$totale_controllo." , totale_ordine:".$order->getBaseGrandTotal()."\n";
                    //foreach ($tmp_lista as $item)
                    //    print_r ($item);

                } else {
                    foreach ($tmp_lista as $item)
                        array_push($lista_record, $item);
                }





            } //end if

        } //end for



        /**
         * SEZIONE CREDITMEMO
         */



        foreach ($lista_creditmemo as $obj) {
            $order_id = $obj->order_id;
            $creditmemo_id = $obj->creditmemo_id;
            $info_creditmemo = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);

            $order = Mage::getModel('sales/order')->load($order_id);

            $billingAddress = $order->getBillingAddress();                                                  //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $billingAddressCountryid = strtolower($billingAddress->getCountryId());                         //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $orderCountry = CountryDBHelper::getCountryDetails($billingAddressCountryid);                   //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $iva = $orderCountry->iva;

            $totale_controllo = 0;
            //Rino 19/09/2016
            //if ( ($billingAddressCountryid == $country->country_id && $sopra_soglia=='1')  ||  ( $orderCountry->sopra_soglia == '0' && $sopra_soglia=='0') ) {// RINO 9/7/2016 Se l'ordine  è del paese corrente o è in sotto-soglia ($orderCountry->sopra_soglia == '0' è possibile solo se il $country->country_id è IT tutti gli a tri casi sono stati esclusi a monte
            if ($orderCountry->codice_ente == $codice_ente) {  //RINO 19/09/2016
                $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
                $order_custom_attributes = $orderDBHelper->getCustomAttributes();

                $manager = new PaymentDBHelper($order->getDwOrderNumber());
                $paymentObj = $manager->getPaymentInfo();



                //$trx_header_id = CountersHelper::getTrxHeaderId();
                $trx_header_id = $info_creditmemo->bill_number;
                $tipo_transazione = "R";

                $tessera_fidelity = '';
                if ($order_custom_attributes['loyaltyCard'])
                    $tessera_fidelity = str_pad($order_custom_attributes['loyaltyCard'], 9, '0', STR_PAD_LEFT);

                $punti_guadagnati = $order_custom_attributes['rewardPoints'];
                $punti_spesi = $order_custom_attributes['spentPoints'];
                $tmp = $order->getBillingAddress()->getData();
                $cap = $tmp['postcode'];


                //print_r($order->getData());
                $valuta = "EUR";
                //27-10-2016 (guest checkout)
                if ($order->getData('dw_is_authenticated')=='false') {
                    echo "\nUTENTE GUEST\n";
                    $codice_cliente_dw = $this->config->getFakeUserId($billingAddress->getCountryId());
                    //$codice_cliente_dw = $order->getDwCustomerId();
                    $codice_cliente = '000000';



                } else {
                    //inizio - Modifica per codice cliente per mettere lo stesso di SG
                    $codice_cliente = $order->getCustomerId();
                    $customerTmpHelper = Mage::getModel('customer/customer');
                    $customerTmp = $customerTmpHelper->load($order->getCustomerId());
                    $sg_user_id = $customerTmp->getData('sg_user_id');
                    if (!$sg_user_id) $sg_user_id = $codice_cliente;
                    $codice_cliente = $sg_user_id;
                    $codice_cliente_dw = $order->getDwCustomerId();
                    $codice_cliente=ltrim($codice_cliente,'0'); //RINO 27/07/2016
                }
                //- fine Modifica per codice cliente per mettere lo stesso di SG


                $order_no = $order->getDwOrderNumber();
                $data_ordine = $order->getCreatedAt();
                $newDate_ordine = date("d/m/Y H:i:s", strtotime($order->getDwOrderDatetime()));

                //$trx_date = $paymentObj->timestamp;   //RINO 09/09/2016
                //$trx_date = date_format(date_create_from_format('d/m/Y', $info_creditmemo->bill_date), 'Y-m-d');//In realtà per le note di credito occorre prendere la data dello scontrino di nota di credito
                //ALESSIO -- modifica per non avere più ordini della data uguale al giorno nel quale Zennaro lo riceve
                $trx_date = date_format(date_create_from_format('d/m/Y', $info_creditmemo->bill_date), 'Y-m-d');//In realtà per le note di credito occorre prendere la data dello scontrino di nota di credito

                //var_dump(substr($info_creditmemo->created_at,0,10));	// prendo la data da created_at (che è YYYY-mm-dd hh:mm:ss
                // $trx_date = /*substr(*/ $info_creditmemo->created_at;//,0,10);		//e la metto come trx_date  // RINO 09/09/2016
                //var_dump($trx_date);
                //exit;
                //FINE ALESSIO

                /* FIX 05112015*/
                /* per CO perchè la data non esiste */

                $payment = $order->getPayment();
                $payment_method_selected = $payment->getMethod();

                //if ($payment_method_selected=='cashondelivery') {
                //    $trx_date = date('Y-m-d');
                //}
                /* END FIX per CO*/

                $tmp_lista = array();

                $esenziona_iva = '0'; //richiesta da Zennaro il 30/06/2015
                $newDate_trx_date = date("d/m/Y H:i:s", strtotime($trx_date));
                $trx_header = new TrxHeaderRecord($codice_cassa, $trx_header_id, $newDate_trx_date, $tipo_transazione, $tessera_fidelity,
                    $punti_guadagnati, $punti_spesi, $cap, $valuta, $codice_cliente, $codice_cliente_dw,
                    $order_no, $newDate_ordine, $esenziona_iva);
                //array_push($lista_record, $trx_header);
                array_push($tmp_lista, $trx_header);


                $crdetails = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);
                //TRX DISCOUNT
                $discount_value = $crdetails->discount_value;

                if ($discount_value) {

                    $valore_promo = number_format($discount_value * 1, 2);
                    $valore = str_pad(str_replace('.','', $valore_promo ),7,'0',STR_PAD_LEFT);
                    $valore = str_pad(str_replace(',','', $valore ),7,'0',STR_PAD_LEFT);
                    $trx_discount = new TrxDiscountRecord($valore, $crdetails->discount_description);
                    //array_push($lista_record, $trx_discount);

                    array_push($tmp_lista, $trx_discount);

                    $totale_controllo -= $discount_value;
                    echo "\nTotale_controllo: ".$totale_controllo;
                }


                //ITEM_STOCK
                //$lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
                $lines = CreditMemoHelper::getCreditMemoItems($creditmemo_id);

                foreach ($lines as $line) {
                    //print_r($line);
                    $sku = $line->sku;
                    //$qty = -1 * $line->qty;

                    $qty =  1 * $line->qty; //26102015 -> valore positivo secondo Zennaro
                    //$bp = number_format($line->base_price,2);
                    $bp = number_format($line->base_price + $line->tax_amount,2);
                    $dv = number_format($line->discount_value,2);
                    $unit_price = str_pad(str_replace('.','', $bp),7,'0',STR_PAD_LEFT);
                    $unit_price = str_pad(str_replace(',','', $unit_price),7,'0',STR_PAD_LEFT);
                    $discount_value = str_pad(str_replace('.','', $dv),7,'0',STR_PAD_LEFT);
                    $discount_value = str_pad(str_replace(',','', $discount_value),7,'0',STR_PAD_LEFT);

                    $item_dw_promo_id = '';
                    $item_dw_extra_points = '';
                    $item_dw_return_points = '';

                    $stockRecord = new ItemStockRecord($sku, $unit_price, $qty, $discount_value, $item_dw_extra_points, $item_dw_return_points, $item_dw_promo_id);
                    //array_push($lista_record, $stockRecord);
                    array_push($tmp_lista, $stockRecord);

                    $totale_controllo += ( ($bp * $qty) - $dv);
                    echo "\nTotale_controllo: ".$totale_controllo;


                }


                //ITEM_FEE Spedizione la prende dalla nota di credito. Se c'è va quindi riaccreditato il trasporto


                //$shipping_amount = $crdetails->shipping_amount * -1;
                $shipping_amount = $crdetails->shipping_amount; //26102015 -> valore positivo secondo Zennaro
                echo "\nShipping_amount_credit: ".$shipping_amount;
                $shippingAmount = number_format($shipping_amount,2);
                $shippingAmount_fmt = str_pad(str_replace('.','', $shippingAmount),7,'0',STR_PAD_LEFT);

                $shippingDiscount = number_format(0,2);
                $shippingDiscount_fmt = str_pad(str_replace('.','', $shippingDiscount),7,'0',STR_PAD_LEFT);
                $trx_discount = new ItemFeeRecord('Shipping Charges', $shippingAmount_fmt, $shippingDiscount_fmt, '');
                //array_push($lista_record, $trx_discount);
                if ($shipping_amount>0)
                    array_push($tmp_lista, $trx_discount);
                $totale_controllo += $crdetails->shipping_amount;

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

                // array_push($lista_record, $txr_tender);
                array_push($tmp_lista, $txr_tender);


                if (round($totale_controllo,2) != round($crdetails->base_grand_total,2)) {
                    echo "\nAttenzione squadratura: totale_controllo=".$totale_controllo." , totale_ordine:".$crdetails->base_grand_total."\n";
                    //foreach ($tmp_lista as $item)
                    //    print_r ($item);

                } else {
                    foreach ($tmp_lista as $item)
                        array_push($lista_record, $item);
                }


            } //end if

        } //end for CREDIT MEMO


        //REGISTER_CLOSE
        $end_date = date('d/m/Y H:i:s');
        $record = new RegisterCloseRecord($end_date);
        array_push($lista_record, $record);

        //scrive i record
        return $lista_record;
    }

    private function writeRecordToFile($lista_record_it, $lista_record_es) {

        $content = array();
        foreach ($lista_record_it as $record) {
            echo "\n".$record->getLine();
            $content[] = $record->getLine();
        }
        foreach ($lista_record_es as $record) {
            echo "\n".$record->getLine();
            $content[] = $record->getLine();
        }

        $timestamp = date('Ymdhis');
        $codiceShop = $this->config->getEcommerceShopCode();
        $file_name = "ESL_".$codiceShop."_SALES_".$timestamp.".TXT";
        $directory = $this->config->getBillExportOutboundDir();
        $full_name = $directory."/".$file_name;

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
    private function getListaOrdiniDaExportare( $start = null, $end = null, $status='complete') {

        $con = OMDBManager::getMagentoConnection();

        //$sql ="SELECT increment_id FROM sales_flat_order WHERE (created_at BETWEEN '$start' AND '$end')
        // AND status='$status'"; //nel flusso scontrini ci vanno anche quelli che hanno chiesto fattura
        //nel flusso scontrini ci vanno anche quelli che hanno chiesto fattura

        //$sql ="SELECT increment_id FROM sales_flat_order WHERE  bill_date = '$start' AND status='$status'";  // RINO 03/09/2016
        $sql ="SELECT increment_id FROM sales_flat_order WHERE  bill_date = '$start' AND (status='$status' OR status='closed')";  // RINO 03/09/2016


        /*$sql ="SELECT increment_id FROM sales_flat_order WHERE ".
         " bill_number in (153974,153975,153976,153977,153978,153979,153980,153981,153982,153983,153984,153985,153986,153987,153988,153989,153990,153991,153992,153993,153994,153995,153996,153997,153998)".
         " AND (status='$status' OR status='closed')";*/

        /*$sql ="SELECT increment_id FROM sales_flat_order WHERE ".
            " bill_number in (154794)".
            " AND (status='$status' OR status='closed')";*/

        //$sql ="SELECT increment_id FROM sales_flat_order WHERE dw_order_number = '00285422'";



        //echo "\nLog: ".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $this->log->LogDebug("Record trovati:".$row->increment_id);
            $lista[] = $row->increment_id;
        }
        OMDBManager::closeConnection($con);

        //$this->log->LogDebug("Record trovati:");
        echo "\nordini\n";
        print_r($lista);
        return $lista;
    }

    private function getListaCreditMemoDaExportare( $start = null, $end = null) {

        $con = OMDBManager::getMagentoConnection();


        $sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE bill_date = '$start'";

        /*$sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE ".
            " bill_number in (154794)";*/



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
        echo "\nnote di credito\n";
        print_r($lista);
        return $lista;

    }

    private function getListaOrdiniDaExportareFromOrderNumber($lista_dw_order_number) {

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

    private function getListaCreditMemoDaExportareFromOrderId( $order_id) {

        $con = OMDBManager::getMagentoConnection();


        $sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE order_id = '$order_id'";


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

        return $lista;

    }




    public function exportManualeNoteDicredito($lista_bill_number) {

        $all_records= array();

        $i=0;
        foreach ($lista_bill_number as $bill_number) {
            $order = BillHelper::getDWOrderByBillNumberFromCreditMemo($bill_number);
            if ($order!=null) {
                $lista_creditmemo[] = $order;
                $i++;
            }
        }
        echo "\n$i";
        print_r($lista_creditmemo);



        $country_base = CountryDBHelper::getCountryDetails("IT");
        $records=$this->generateBillExport($country_base, [], $lista_creditmemo, 1);
        foreach ($records AS $record ) array_push($all_records,$record);

        $this->writeRecordToFile($all_records);
    }


}

//TODO METTERE LA DATA AUTOMATICA
$t = new BillExport();
/*$start_date="2016-08-08 00:00:00";
$end_date="2016-08-08 23:59:59";
*/

$date= date('Y-m-d');
$start_date = date('d/m/Y', strtotime('-1 day', strtotime($date)));
$end_date = date('d/m/Y', strtotime('-1 day', strtotime($date)));

//per la credit memo siccome viene generata dopo la mezzanotte occorre mettere la stessa giornata
/*TODO da committare la modifica domani 29/10/2015*/


$start_date_cm = date('d/m/Y', strtotime('-1 day', strtotime($date)));
$end_date_cm = date('d/m/Y', strtotime('-1 day', strtotime($date)));

//echo "\nStart: ".$start_date;
//echo "\nEnd: ".$end_date;

/*for ($i=6;$i <=6 ;$i++) {
    $data = str_pad($i, 2 , '0', STR_PAD_LEFT);
    $start_date = $data . "/09/2016";
    $end_date = $data . "/09/2016";

    $start_date_cm = $data . "/09/2016";
    $end_date_cm = $data . "/09/2016";

    $t->exportPerCountry($start_date, $end_date, $start_date_cm, $end_date_cm);
}*/



/*$date="06/10/2016";
$start_date = $date;
$end_date = $date;

$start_date_cm = $date;
$end_date_cm = $date;*/


//$t->exportPerCountry($start_date, $end_date, $start_date_cm, $end_date_cm);
/*
$date="18/10/2016";
$start_date = $date;
$end_date = $date;

$start_date_cm = $date;
$end_date_cm = $date;
*/

$t->exportPerEnte($start_date, $end_date, $start_date_cm, $end_date_cm);

// MANUALE X NOTE DI CREDITO
//$lista_bill_number=array(151556,151557,151558,151559,151560,151561,151562,151563,151564,151565,151566,151567,151568,151569,151570,151571,151572,151573,151574,151575,151576,151577,151578,151579,151580,151581,151582,151583,151584,151585,151586,151587,151588,151589,151590,151591,151592,151593,151594,151595,151596,151597,151707,151708,151709,151710,151711,151712,151713,151714,151715,151716,151717,151718,151719,151720,151721,151722,151723,151724,151725,151730,151731,151732,151733,151734,151735,151736,151737,151738,151739,151740,151742,151743,151892,151893,151894,151895,151896,151897,151898,151899,151900,151901,152108,152109,152110,152111,152588,152703,152704,152705,152706,152707,152708,152709,152710,152711,152712,152713,152714,152715,152716,152717,152718,152719,152720,152721,152722,152723,152724,152725,152726,152727,152728,152730,152731,152732,152733,152734,153484,153485,153486,153487,153488,153489,153490,153491,153492,153493,153494,153496,153497,153498,153499,153500,153501,153502,153503,153504,153505,153506,153507,153508,153509,153510,153511,153513,153514,153515,153516,153517,153520,153521,153522,153523,153524,153525,153526,153527,153528,153529,151890,151891,152729,153495,153512,153518,153519);
//$lista_bill_number=array(153974,153975,153976,153977,153978,153980,153981,153982,153983,153984,153986,153987,153988,153990,153991,153992,153993,153994,153995,153996,153998);
//$lista_bill_number=array(153530);
//$t->exportManualeNoteDicredito($lista_bill_number);




