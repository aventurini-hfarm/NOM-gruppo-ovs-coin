<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:33
 */

require_once realpath(dirname(__FILE__))."/../common/OMDBManager.php";
require_once realpath(dirname(__FILE__))."/../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../common/CountersHelper.php";
require_once realpath(dirname(__FILE__))."/../common/FileGenerator.php";
require_once realpath(dirname(__FILE__))."/../omdb/OrderDBHelper.php";
require_once realpath(dirname(__FILE__))."/../omdb/PaymentDBHelper.php";
require_once realpath(dirname(__FILE__))."/../dw2om/orders/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__)) . "/../omdb/PaymentDBHelper.php";
require_once realpath(dirname(__FILE__)) . "/../creditmemo/CreditMemoHelper.php";
require_once realpath(dirname(__FILE__)) .'/../Utils/PHPExcel/Classes/PHPExcel/IOFactory.php';
require_once realpath(dirname(__FILE__)) .'/../Utils/PHPExcel/Classes/PHPExcel.php';
require_once realpath(dirname(__FILE__)) . "/../Utils/mailer/PHPMailerAutoload.php";
require_once realpath(dirname(__FILE__)) . "/../Utils/pdf/dompdf_config.inc.php";
require_once realpath(dirname(__FILE__))."/../omdb/CountryDBHelper.php"; //ESTERO

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();


class DailySalesExport {

    private $status_to_export = "complete";

    private $log;
    private $config;
    private $start_date;
    private $end_date;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/reports.log',KLogger::DEBUG);


    }

    /**
     * Inizia export flusso scontrini in base al range temporale
     * @param $start data inizio
     * @param $end data fine
     */
    public function export($start, $end) {

        $this->start_date = $start;
        $this->end_date = $end;

        $date_tmp= $start;
        $start_date_tmp = date('d/m/Y', strtotime($date_tmp));
        $end_date_tmp = date('d/m/Y', strtotime($date_tmp));
        // echo "\nDATA: ".$start_date_tmp;

        $this->log->LogInfo("Start Generazione Report");
        $lista_ordini = $this->getListaOrdiniDaExportare($start, $end, $this->status_to_export);
        $lista_creditmemo = $this->getListaCreditMemoDaExportare($start, $end);

        if ($lista_ordini || $lista_creditmemo) {
            $records = $this->generateReport($lista_ordini, $lista_creditmemo);

            $this->writeRecordToFile($records);
        }
        else
            $this->log->LogInfo("\nNessun ordine includere nel report");



    }

    private function getCountryIva($order) {     //RINO 26/09/2016

        $billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());
        $billing_country = $billingAddress->getData('country_id');
        $country_details = CountryDBHelper::getCountryDetails($billing_country);
        $country_iva= $country_details->iva;
        return $country_iva;
    }

    private function getRecordSpedizioni($order, $country) {
        //ITEM_FEE Spedizione
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();

        $promoObjArray = $orderDBHelper->getShippingPromotion();
        //$shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);  // RINO 09/08/2016
        $shippingAmount = number_format($order->getShippingInclTax() + ($order->getBaseShippingDiscountAmount() * -1),2);  // RINO 09/08/2016
        //$shippingAmount_fmt = str_pad(str_replace('.','', $shippingAmount),7,'0',STR_PAD_LEFT);
        $shippingDiscount = 0;
        foreach ($promoObjArray as $promoObj) {
            $shippingDiscount = number_format(($promoObj->value * -1),2);
        }
        //$shippingDiscount_fmt = str_pad(str_replace('.','', $shippingDiscount),7,'0',STR_PAD_LEFT);

        $punti_guadagnati = $order_custom_attributes['rewardPoints'];
        $punti_spesi = $order_custom_attributes['spentPoints'];

        $record = new stdClass();
        $record->sku = "Spese Spedizione";
        $record->descrizione = '';
        $record->qta = '';
        $record->prezzo = 0;
        $record->sconto = 0;
        $record->codice_promo = $promoObj->promotion_id;

        $shipping_value = $shippingAmount - $shippingDiscount;
        $amount = number_format($shipping_value,2);

        /* //RINO 26/09/2016
        if ($country == "IT")
            $imponibile_tmp = $shipping_value/1.22;
        if ($country == "ES")
            $imponibile_tmp = $shipping_value/1.21;
        */

        $country_iva= $this->getCountryIva($order);         //RINO 26/09/2016
        $imponibile_tmp = $shipping_value/$country_iva;     //RINO 26/09/2016

        $imponibile = number_format(round($imponibile_tmp,2),2);

        $iva = $amount - $imponibile;
        $record->totale_netto = number_format($imponibile,2);
        $record->iva = number_format(round($iva,2),2);
        $record->totale = number_format($amount,2);

        $record->punti_spesi = $punti_spesi;
        $record->punti_guadagnati = $punti_guadagnati;
        $record->punti_resi = '';
        $record->spese_spedizione = $amount;
        $record->sconto_spese_spedizione = $shippingDiscount;

        //echo "\nORDINE: ".$order->getDwOrderNumber();
        //print_r($record);

        return $record;

    }



    private function getCustomerInfoRecord($order) {
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();

        $codice_cliente = $order->getCustomerId();

        //inizio - Modifica per codice cliente per mettere lo stesso di SG
        $customerTmpHelper = Mage::getModel('customer/customer');
        $customerTmp = $customerTmpHelper->load($order->getCustomerId());
        $sg_user_id = $customerTmp->getData('sg_user_id');
        if (!$sg_user_id) $sg_user_id = $codice_cliente;
        $codice_cliente = $sg_user_id;
        //- fine Modifica per codice cliente per mettere lo stesso di SG

        $codice_cliente_dw = $order->getDwCustomerId();

        $record = new stdClass();
        $record->codice_cliente = $codice_cliente;

        $record->web_user_id = $codice_cliente_dw;

        $bill_to_info= $order->getBillingAddress();
        //$numero_doc = str_pad(CountersHelper::getInvoiceNumber(date('Y')), 4,'0', STR_PAD_LEFT);
        $numero_doc = str_pad(ltrim($order->getData('invoice_number'),'0'),4,'0',STR_PAD_LEFT);
        $data_documento_fattura = $order->getData('invoice_date');

        $ragione_sociale = $order_custom_attributes['ragioneSociale'];
        $ragione_sociale_1 = $bill_to_info->getFirstname();
        $ragione_sociale_2 = $bill_to_info->getLastname();

        if ($ragione_sociale) {
            if (strlen($ragione_sociale>40)) {
                $ragione_sociale_1 = substr($ragione_sociale, 0, 39);
                $ragione_sociale_2 = substr($ragione_sociale, 40);
            } else {
                $ragione_sociale_1 = $ragione_sociale;
                $ragione_sociale_2 = "";

            }
        }
        $rag_sociale_nome = $ragione_sociale_1;
        $rag_sociale_cognome = $ragione_sociale_2;
        $tmp = $order->getBillingAddress()->getData();
        $cap = $tmp['postcode'];


        $record->nome_cognome = $rag_sociale_nome." ".$rag_sociale_cognome;

        $customerId = $order->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $record->email = $customer->getEmail();

        $record->coin_card = $order_custom_attributes['loyaltyCard'];
        $record->cap = $cap;

        return $record;

    }

    public function getOrderInfoRecord($order) {
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();
        $newDate_ordine = date("d/m/Y H:i:s", strtotime($order->getDwOrderDatetime()));
        $scontrino = $order->getData('bill_number');
        $data_documento = $order->getData('bill_date');

        $fattura = $order->getData('invoice_number');
        $data_documento_fattura = $order->getData('invoice_date');

        $record = new stdClass();
        $record->data_ordine = $newDate_ordine;
        $record->destinazione="Cliente";
        $record->tipo_trx ="V";
        $record->numero_scontrino = $scontrino;
        $record->data_scontrino = $data_documento;
        $record->numero_fattura = $fattura;
        $record->numero_ordine = $order->getDwOrderNumber();
        $record->sorgente = $order_custom_attributes['deviceCode'];


        //Estero
        $order_shipping_address= $order->getShippingAddress();
        $record->country_id = $order_shipping_address->country_id;

        //qui devo togliere le spedizioni
        $tmpRecordSpedizioni = $this->getRecordSpedizioni($order);
        $tmp_amount = $order->getBaseGrandTotal() - $tmpRecordSpedizioni->totale;

        $row_amount = number_format($tmp_amount,2);

        /* //RINO 26/09/2016
        if ($record->country_id == 'IT')
            $imponibile_tmp = $row_amount/1.22;
        if ($record->country_id == 'ES')
            $imponibile_tmp = $row_amount/1.21;
        */

        $country_iva= $this->getCountryIva($order);  //RINO 26/09/2016
        $imponibile_tmp = $row_amount/$country_iva; //RINO 26/09/2016

        $imponibile = number_format(round($imponibile_tmp,2),2);

        $iva = $row_amount - $imponibile;


        $record->totale_netto = number_format($imponibile,2);
        $record->iva = number_format(round($iva,2),2);

        //rimetto il totale corretto:
        $row_amount = number_format($order->getBaseGrandTotal(),2);
        $record->totale = number_format($row_amount,2);



        return $record;

    }

    public function getPaymentInfoRecord($order) {

        $manager = new PaymentDBHelper($order->getDwOrderNumber());
        $paymentObj = $manager->getPaymentInfo();



        //ITEM_TENDER
        $payment = $order->getPayment();
        $payment_method_selected = $payment->getMethod();
        //print_r($payment->getData());
        //echo "\nPayment Method: ".$payment_method_selected;
        $orderValue = number_format($order->getBaseGrandTotal(),2);
        $orderValue_fmt = str_pad(str_replace('.','', $orderValue),7,'0',STR_PAD_LEFT);

        $record = new stdClass();
        if ($payment_method_selected=='ccsave') {
            //$txr_tender = new ItemTenderRecord('CC',$payment->getCcType(), $orderValue_fmt);
            $record->tipo_pagamento = 'CC';
            $record->description =$payment->getCcType()."|".$payment->getCcOwner()."|".$payment->getCcLast4();
        }elseif ($payment_method_selected=='cashondelivery') {
            //$txr_tender = new ItemTenderRecord('CO','', $orderValue_fmt);
            $record->tipo_pagamento = 'CO';
            $record->description ='CONTRASSEGNO';
        }
        else {
            //PayPal
            //$txr_tender = new ItemTenderRecord('PP','',$orderValue_fmt );
            $record->tipo_pagamento = 'PP';
            $record->description ='';
        }

        return $record;

    }

    private function getOrderLinesRecord($order, $country) {
        //ITEM_STOCK
        $increment_id = $order->getIncrementId();
        $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);

        $lista_record = array();
        foreach ($lines as $line) {
            $record = new stdClass();
            //print_r($line);
            $sku = $line['sku'];
            $qty = $line['order_quantity'];

            $unit_price = str_pad(str_replace('.','', $line['unit_price']),7,'0',STR_PAD_LEFT);
            $discount_value = str_pad(str_replace('.','', $line['discount_value']),7,'0',STR_PAD_LEFT);
            $item_dw_promo_id = $line['item_dw_promo_id'];

            //$stockRecord = new ItemStockRecord($sku, $unit_price, $qty, $discount_value, $item_dw_extra_points, $item_dw_return_points, $item_dw_promo_id);
            //array_push($lista_record, $stockRecord);
            $record->sku = $sku;
            $record->descrizione = $line['description'];
            $record->qta = $qty;
            $record->prezzo_cliente = $line['unit_price'];
            $record->sconto_linea = $line['discount_value'];
            $record->codice_promo = $item_dw_promo_id;

            $row_amount = number_format($record->prezzo_cliente * $record->qta,2);

            /* //RINO 26/09/2016
            if ($country == "IT")
                $imponibile_tmp = $row_amount/1.22;
            if ($country == "ES")
                $imponibile_tmp = $row_amount/1.21;
            */

            $country_iva= $this->getCountryIva($order);  //RINO 26/09/2016
            $imponibile_tmp = $row_amount/$country_iva; //RINO 26/09/2016

            $imponibile = number_format(round($imponibile_tmp,2),2);

            $iva = $row_amount - $imponibile;

            $record->totale_netto = number_format($imponibile,2);
            $record->iva = number_format(round($iva,2),2);
            $record->totale = number_format($row_amount,2);

            array_push($lista_record, $record);

        }

        return $lista_record;

    }


    private function getCreditMemoLinesRecord($creditmemo_id, $order) {

        $lines = CreditMemoHelper::getCreditMemoItems($creditmemo_id);

        // print_r($lines);
        // echo "|NFINE LINES";
        //ITEM_STOCK
        //$increment_id = $order->getIncrementId();
        //$lines = MagentoOrderHelper::getOrderLineDetails($increment_id);

        $lista_record = array();
        foreach ($lines as $line) {
            // echo "\nDump line";
            // print_r($line);
            $record = new stdClass();
            //print_r($line);
            $sku = $line->sku;
            $qty = -1 * $line->qty;
            $record->sku = $sku;
            $record->descrizione = $line->name;
            $record->qta = $qty;
            $record->prezzo_cliente = $line->base_price;
            $record->sconto_linea = 0;
            $record->codice_promo = '';

            $row_amount = number_format(-1 * $line->row_total,2);

            /* //RINO 26/09/2016
            if ($country == "IT")
                $imponibile_tmp = $row_amount/1.22;
            if ($country == "ES")
                $imponibile_tmp = $row_amount/1.21;
            */

            $country_iva= $this->getCountryIva($order);         //RINO 26/09/2016
            $imponibile_tmp = $row_amount/$country_iva;         //RINO 26/09/2016

            $imponibile = number_format(round($imponibile_tmp,2),2);

            $iva = $row_amount - $imponibile;

            $record->totale_netto = $imponibile;
            $record->iva = $iva;
            $record->totale = $row_amount;

            array_push($lista_record, $record);


        }

        return $lista_record;

    }

    private function getItemFeeRecord($order) {
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $increment_id = $order->getIncrementId();
        $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
        //ITEM_FEE altri opzioni (montaggio)

        $lista_record = array();
        foreach ($lines as $line) {
            $item_has_options = $line['item_has_options'];
            //print_r($line);
            if ($item_has_options=='1') {

                $options = $orderDBHelper->getItemOptions($line['sku']);
                print_r($options);

                /*foreach ($options as $option) {
                    if ( ($option->option_key=='product-id')  && ($option->option_value=='Montaggio') ){
                        $valore = $options['base-price']->option_value;
                        $valoreMontaggio = str_pad(str_replace('.','', $valore),7,'0',STR_PAD_LEFT);
                        $valoreScontoMontaggio = str_pad(str_replace('.','', '0.00'),7,'0',STR_PAD_LEFT);


                        //$item_fee = new ItemFeeRecord('Montaggio', $valoreMontaggio, $valoreScontoMontaggio, '');
                        //array_push($lista_record, $item_fee);

                        $record = new stdClass();
                        $record->sku="Montaggio";
                        $record->descrizione = '';
                        $record->qta = '';
                        $record->prezzo = $valore;



                        $amount = number_format($valore,2);

                        $imponibile_tmp = $valore/1.22;

                        $imponibile = number_format(round($imponibile_tmp,2),2);

                        $iva = $amount - $imponibile;
                        $record->totale_netto = $imponibile;
                        $record->iva = $iva;
                        $record->totale = $amount;

                        array_push($lista_record, $record);
                    }
                }*/

            }
        }

        return $lista_record;

    }


    private function generateReport($lista_ordini, $lista_creditmemo) {
        $lista_record = array();
        $start_date = date('d/m/Y H:i:s');
        //$record = new RegisterOpenRecord($this->config->getEcommerceShopCode(), $start_date);
        $codice_cassa = $this->config->getEcommerceShopCodiceCassa();


        foreach ($lista_ordini as $increment_id) {
            $record = new stdClass();

            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');

            $record_customer_info = $this->getCustomerInfoRecord($order);
            $record_order_info = $this->getOrderInfoRecord($order);
            $record_payment_info = $this->getPaymentInfoRecord($order);
            //$record_merchandize_promo = $this->getMerchandizePromo($order);
            //$record_discount_promo = $this->getDiscountRecord($order);
            $lista_item_fee = $this->getItemFeeRecord($order);
            $record_spedizioni = $this->getRecordSpedizioni($order, $record_order_info->country_id);
            $lista_righe_ordine = $this->getOrderLinesRecord($order, $record_order_info->country_id);

            //Crea record finale
            $record = new stdClass();
            $record->numero_ordine=$record_order_info->numero_ordine;
            $record->numero_fattura=$record_order_info->numero_fattura;
            $record->numero_scontrino=$record_order_info->numero_scontrino;
            $record->data_scontrino=$record_order_info->data_scontrino;

            $record->tassa_spedizione = number_format($record_spedizioni->totale_netto,2);
            $record->iva_tassa_spedizione = number_format($record_spedizioni->iva,2);

            $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
            $record->data_pagamento=$record_order_info->data_pagamento;
            $record->termini_pagamento = $record_payment_info->description;
            if ($record->tipo_pagamento=='PP') {
                $record->termini_pagamento="PAYPAL";
            }
            if ($record->tipo_pagamento=='CO') {
                $record->termini_pagamento="CONTRASSEGNO";
            }

            $record->totale_merce = number_format($record_order_info->totale_netto,2);
            $record->iva = number_format(round($record_order_info->iva,2),2);  //RINO 09/08/2016
            $record->vendita = number_format($record_order_info->totale,2);

            //estero
            $record->country_id = $record_order_info->country_id;

            $qta = 0;
            //RIGHE ORDINE
            foreach ($lista_righe_ordine as $record_riga) {

                $qta += $record_riga->qta;
            }

            $record->qta = $qta;

            array_push($lista_record, $record);

        } //end for

        //Gestione RESI

        foreach ($lista_creditmemo as $obj) {
            //echo "\nCreditMemo: ".$obj->creditmemo_id;
            $order_id = $obj->order_id;
            $creditmemo_id = $obj->creditmemo_id;
            $info_creditmemo = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);

            // print_r($info_creditmemo);



            $order = Mage::getModel('sales/order')->load($order_id);

            $record_customer_info = $this->getCustomerInfoRecord($order);
            $record_order_info = $this->getOrderInfoRecord($order);
            $record_payment_info = $this->getPaymentInfoRecord($order);
            //$record_merchandize_promo = $this->getMerchandizePromo($order);
            //$record_discount_promo = $this->getDiscountRecord($order);
            $lista_item_fee = $this->getItemFeeRecord($order);
            $record_spedizioni = $this->getRecordSpedizioni($order, $record_order_info->country_id);
            $lista_righe_creditmemo = $this->getCreditMemoLinesRecord($creditmemo_id, $order);
            //print_r($lista_righe_creditmemo);
            //Crea record finale

            $record = new stdClass();
            $record->numero_ordine=$record_order_info->numero_ordine;
            $record->numero_fattura=$info_creditmemo->invoice_number;
            $record->numero_scontrino=$info_creditmemo->bill_number;
            $record->data_scontrino=$info_creditmemo->bill_date;

            $record->tassa_spedizione = 0;
            $record->iva_tassa_spedizione = 0;

            $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
            $record->data_pagamento=$record_order_info->data_pagamento;
            $record->termini_pagamento = $record_payment_info->description;
            if ($record->tipo_pagamento=='PP') {
                $record->termini_pagamento="PAYPAL";
            }
            if ($record->tipo_pagamento=='CO') {
                $record->termini_pagamento="CONTRASSEGNO";
            }


            $row_amount = number_format($info_creditmemo->grand_total,2);

            /* //RINO 26/09/2016
            if ($record_order_info->country_id == "IT")
                $imponibile_tmp = $row_amount/1.22;
            if ($record_order_info->country_id == "ES")
                $imponibile_tmp = $row_amount/1.21;
            */

            $country_iva= $this->getCountryIva($order);         //RINO 26/09/2016
            $imponibile_tmp = $row_amount/$country_iva;         //RINO 26/09/2016


            $imponibile = number_format(round($imponibile_tmp,2),2);
            $iva = $row_amount - $imponibile;

            $record->totale_merce = number_format(-1 *$imponibile,2);
            $record->iva = number_format(-1 * $iva,2);


            $record->vendita = number_format( -1 * $info_creditmemo->grand_total,2);

            //estero
            $record->country_id = $record_order_info->country_id;


            $qta = 0;
            //RIGHE ORDINE
            foreach ($lista_righe_creditmemo as $record_riga) {

                $qta += $record_riga->qta;
            }

            $record->qta =  $qta;

            array_push($lista_record, $record);


        } //end for

        return $lista_record;
    }
    private function writeRecordToFile($lista_record) {


        $path_template = realpath(dirname(__FILE__)) .'/template';
        $excel = PHPExcel_IOFactory::createReader('Excel2007');
        $excel = $excel->load($path_template."/daily_sales_estero.xlsx");
        $excel->setActiveSheetIndex(0);

        $objWorksheet = $excel->getActiveSheet();
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                )
            ),
            'font'  => array(
                'size'  => 7,
                'name'  => 'Times New Roman'
            )
        );

        $styleBoldArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                )
            ),
            'font'  => array(
                'bold' => true,
                'size'  => 10,
                'name'  => 'Times New Roman'
            )
        );




        $riga = 12;
        $totale_g=0;
        $totale_h=0;
        $totale_i=0;
        $totale_j=0;
        $totale_k=0;
        $totale_l=0;
        foreach ($lista_record as $record) {
            $cella = "A".$riga;
            $xfIndex = $objWorksheet->getCell('A12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue('OVS Magazzino IT'); //RINO 16/07/2016
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);


            $cella = "B".$riga;
            $cella_estesa = 'B'.$riga.':C'.$riga;
            $objWorksheet->mergeCells($cella_estesa);
            $xfIndex = $objWorksheet->getCell('B12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue(ltrim($record->numero_ordine,'0'));
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
            $objWorksheet->getStyle($cella_estesa)->applyFromArray($styleArray);


            $cella = "D".$riga;
            $xfIndex = $objWorksheet->getCell('D12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->numero_fattura);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

            $cella = "E".$riga;
            $xfIndex = $objWorksheet->getCell('E12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue(ltrim($record->numero_scontrino,'0'));
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

            $cella = "F".$riga;
            $xfIndex = $objWorksheet->getCell('F12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->data_scontrino);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

            $cella = "G".$riga;
            $xfIndex = $objWorksheet->getCell('G12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->qta);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

            $totale_g += $record->qta;
            $cella = "H".$riga;
            $xfIndex = $objWorksheet->getCell('H12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->totale_merce);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
            $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');

            $totale_h += $record->totale_merce;
            $cella = "I".$riga;
            $xfIndex = $objWorksheet->getCell('I12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->iva);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
            $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');

            $totale_i += $record->iva;
            $cella = "J".$riga;
            $xfIndex = $objWorksheet->getCell('J12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->tassa_spedizione);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
            $objWorksheet->getStyle($cella)->applyFromArray($styleArray);
            $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');

            $totale_j += $record->tassa_spedizione;
            $cella = "K".$riga;
            $xfIndex = $objWorksheet->getCell('K12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->iva_tassa_spedizione);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
            $objWorksheet->getStyle($cella)->applyFromArray($styleArray);
            $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');

            $totale_k += $record->iva_tassa_spedizione;
            $cella = "L".$riga;
            $xfIndex = $objWorksheet->getCell('L12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->vendita);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
            $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');

            $totale_l += $record->vendita;
            $cella = "M".$riga;
            $xfIndex = $objWorksheet->getCell('M12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->tipo_pagamento);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

            $cella = "N".$riga;
            $xfIndex = $objWorksheet->getCell('N12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->data_scontrino);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

            $cella = "O".$riga;
            $cella_estesa = 'O'.$riga.':P'.$riga;
            $objWorksheet->mergeCells($cella_estesa);
            $xfIndex = $objWorksheet->getCell('O12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->termini_pagamento);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
            $objWorksheet->getStyle($cella_estesa)->applyFromArray($styleArray);

            //ESTERO
            $cella = "Q".$riga;
            $xfIndex = $objWorksheet->getCell('M12')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue($record->country_id);
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

            $riga++;
        }

        $cella = "G".$riga;
        $objWorksheet->getCell($cella)->setValue($totale_g);
        $objWorksheet->getStyle($cella)->applyFromArray($styleBoldArray);
        $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
        $cella = "H".$riga;
        $objWorksheet->getCell($cella)->setValue($totale_h);
        $objWorksheet->getStyle($cella)->applyFromArray($styleBoldArray);
        $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
        $cella = "I".$riga;
        $objWorksheet->getCell($cella)->setValue($totale_i);
        $objWorksheet->getStyle($cella)->applyFromArray($styleBoldArray);
        $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
        $cella = "J".$riga;
        $objWorksheet->getCell($cella)->setValue($totale_j);
        $objWorksheet->getStyle($cella)->applyFromArray($styleBoldArray);
        $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
        $cella = "K".$riga;
        $objWorksheet->getCell($cella)->setValue($totale_k);
        $objWorksheet->getStyle($cella)->applyFromArray($styleBoldArray);
        $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
        $cella = "L".$riga;
        $objWorksheet->getCell($cella)->setValue($totale_l);
        $objWorksheet->getStyle($cella)->applyFromArray($styleBoldArray);
        $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
        $cella = "F".$riga;
        $objWorksheet->getCell($cella)->setValue('TOTALE');
        $objWorksheet->getStyle($cella)->applyFromArray($styleBoldArray);


        $timestamp = date('Ymd');
        $timestamp = date('Ymd', strtotime('-1 day', strtotime($timestamp)));  // RINO 26/09/2016

        $tmp_date = date("d/m/Y");
        $cella = "C7";
        $objWorksheet->getCell($cella)->setValue("Data: ".$tmp_date);

        $cella = "A8";
        $tmp_date = date("d/m/Y ", strtotime($timestamp));
        $objWorksheet->getCell($cella)->setValue("Da: ".$tmp_date);
        $cella = "A9";
        //$tmp_date = date("d/m/Y ", strtotime($this->end_date));
        $objWorksheet->getCell($cella)->setValue("A: ".$tmp_date);


        $file_name = "DAILY_SALES_".$timestamp.".xlsx";
        $directory = '/tmp';
        $full_name = $directory."/".$file_name;

        $objWriter = PHPExcel_IOFactory::createWriter($excel,'Excel2007');
        $objWriter->save($full_name);


        //CREA HTML
        $objWriter = new PHPExcel_Writer_HTML($excel);
        $html_file_name=  $directory."/DAILY_SALES_".$timestamp.".html";
        $objWriter->save($html_file_name);


        //CREA PDF
        $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
        $rendererLibrary = 'mpdf60';
        $rendererLibraryPath = "/home/OrderManagement/Utils/".$rendererLibrary;
        //echo "\nPATH:".$rendererLibraryPath;
        #$rendererLibraryPath = dirname(__FILE__).'/PHPExcel/Tests/PDF/' . $rendererLibrary;

        if (!PHPExcel_Settings::setPdfRenderer(
            $rendererName,
            $rendererLibraryPath
        )) {
            die(
                'Please set the $rendererName and $rendererLibraryPath values' .
                PHP_EOL .
                ' as appropriate for your directory structure'
            );
        }
        $objWriter = new PHPExcel_Writer_PDF($excel);

        //$objWorksheet->setShowGridlines(true);
        $objWriter->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $pdf_file_name=  $directory."/DAILY_SALES_".$timestamp.".pdf";
        $objWriter->save($pdf_file_name);


        $this->inviaEmail($full_name, $pdf_file_name);



        unset($content);
    }

    /**
     * Estrae la lista ordini direttamente da magento
     * @param null $start
     * @param null $end
     * @return mixed
     */
    private function getListaOrdiniDaExportare($start = null, $end = null, $status='complete') {

        $con = OMDBManager::getMagentoConnection();

        $sql ="SELECT increment_id FROM sales_flat_order WHERE bill_date = '$start'
         AND (status='$status' OR status='closed')"; //nel flusso scontrini ci vanno anche quelli che hanno chiesto fattura

        echo "\nLog: ".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $lista[] = $row->increment_id;
        }
        OMDBManager::closeConnection($con);

        $this->log->LogDebug("Record trovati:");
        print_r($lista);
        return $lista;
    }

    private function getListaCreditMemoDaExportare($start = null, $end = null) {

        $t = new CreditMemoHelper();
        $lista = $t->getListaCreditMemoExportare($start, $end);

        return $lista;
    }

    private function inviaEmail($nome_file, $pdf_file_name = null) {

        //return;
        //info ordine
        $invoicepath = $nome_file;
        $message = "Report Automatico";

        $email_array=array(
            "ecommerce.tracking@ovs.it",
            "ovs.sales@h-farm.com",
            "nomovs@gmail.com",
            "marco.barpi@everis.com",
            "luca.perini@ovs.it",
            "gessica.rizzi@ovs.it"

        );

        $mail = new PHPMailer;
        $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
        $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
        $mail->From 	= 'noreply@ovs.it';
        $mail->FromName = 'OVS Online Store';
        $mail->Subject 	= 'Report Automatico: Daily Sales';
        $mail->Body		= $message;


        $mail->addAttachment($invoicepath);
        if ($pdf_file_name)
            $mail->addAttachment($pdf_file_name);

        $mail->isHTML(false);
        foreach($email_array as $email_addr) {
            $mail->addAddress($email_addr);
        }
        $mail->addBCC('ovs.support@everis.com');

        $mail->send();

    }
}


$t = new DailySalesExport();
//$start_date="2016-08-16 00:00:00";
//$end_date="2016-08-16 23:59:59";

$date= date('Y-m-d');
//$start_date = date('Y-m-d 00:00:00', strtotime('-1 day', strtotime($date)));
//$end_date = date('Y-m-d 23:59:59', strtotime('-1 day', strtotime($date)));

$start_date = date('d/m/Y', strtotime('-1 day', strtotime($date)));
$end_date = date('d/m/Y', strtotime('-1 day', strtotime($date)));


/*for ($i=10; $i <=20; $i++) {
    $data = str_pad($i, 2 , '0', STR_PAD_LEFT);
    $start_date="$data/09/2016";
    $end_date="$data/09/2016";

    $t->export($start_date, $end_date);
}*/

$t->export($start_date, $end_date);
