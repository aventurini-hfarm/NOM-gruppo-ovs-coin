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
require_once realpath(dirname(__FILE__)) . "/../omdb/CountryDBHelper.php";
require_once realpath(dirname(__FILE__)) . "/../creditmemo/CreditMemoHelper.php";
require_once realpath(dirname(__FILE__)) .'/../Utils/PHPExcel/Classes/PHPExcel/IOFactory.php';
require_once realpath(dirname(__FILE__)) .'/../Utils/PHPExcel/Classes/PHPExcel.php';
require_once realpath(dirname(__FILE__)) . "/../Utils/mailer/PHPMailerAutoload.php";
require_once realpath(dirname(__FILE__))."/../omdb/ShipmentDBHelper.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();


class BillExport {

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

        $this->start_date=$start;
        $this->end_date=$end;
        $this->log->LogInfo("Start Generazione Rerpot");

        echo "\nDATA: da ".$start. " a ".$end ;
        $lista_ordini = $this->getListaOrdiniDaExportare($start, $end, $this->status_to_export);
        $lista_creditmemo = $this->getListaCreditMemoDaExportare($start, $end);

        if ($lista_ordini || $lista_creditmemo ) {
            $records = $this->generateReport($lista_ordini, $lista_creditmemo);

            $this->writeRecordToFile($records);
        }
        else
            $this->log->LogInfo("\nNessun ordine includere nel report");



    }

    private function getRecordSpedizioni($order) {
        //ITEM_FEE Spedizione
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();

        $country_iva= $this->getCountryIva($order);  //RINO 13/09/2016

        $promoObj = $orderDBHelper->getShippingPromotion();
        $shippingAmount = $order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1 / $country_iva);
        $shippingDiscount = $promoObj[0]->value * -1 / $country_iva;


        $punti_guadagnati = $order_custom_attributes['rewardPoints'];
        $punti_spesi = $order_custom_attributes['spentPoints'];

        $record = new stdClass();
        $record->sku = "Spese Spedizione";
        $record->descrizione = '';
        $record->qta = '';
        $record->prezzo = '';
        $record->sconto = '';
        $record->codice_promo = $promoObj->promotion_id;

        if ($order->getShippingAmount() == 0  && $shippingDiscount != 0) {
            $imponibile =  $shippingDiscount ;
            $amount = 0;
            $iva = 0;
        } else {
            $lordo = $shippingAmount - $shippingDiscount;
            $imponibile = round($lordo/(1+ $country_iva),2);
            $amount = $lordo;
            $iva = $amount - $imponibile;
        }


        $record->totale_netto = $imponibile;
        $record->iva = $iva;
        $record->totale = $amount;

        $record->punti_spesi = $punti_spesi;
        $record->punti_guadagnati = $punti_guadagnati;
        $record->punti_resi = '';
        $record->spese_spedizione = $amount;
        $record->sconto_spese_spedizione = $shippingDiscount;

        $country_details=$this->getCountryDetails($order);  //RINO 11/10/2016
        $country_id = $country_details->country_id;         //RINO 11/10/2016

        $record->country_id=$country_id;                    //RINO 11/10/2016

        return $record;

    }



    private function getMerchandizePromo($order) {
        //TRX DISCOUNT
        $orderDbHelper = new OrderDBHelper($order->getDwOrderNumber());
        $promoObjArray = $orderDbHelper->getMerchandizePromotion();

        $lista_promo = array();

        $country_iva= $this->getCountryIva($order);  //RINO 13/09/2016

        foreach ($promoObjArray as $promoObj) {

            $valore_promo = $promoObj->value * -1;

            $record = new stdClass();
            $record->sku="Promozione";
            $record->descrizione='';
            $record->qta = 1;
            $record->prezzo = $valore_promo;
            $record->sconto_linea = 0;
            $record->codice_promozione = $promoObj->promotion_id;

            //$imponibile = $order->getBaseDiscountAmount();
            //$amount = $order->getBaseDiscountAmount() * $country_iva;
            $imponibile=$promoObj->value / $country_iva;
            $amount = $promoObj->value;
            $iva = $amount - $imponibile;

            $record->totale_netto = $imponibile;
            $record->iva = $iva;
            $record->totale = $amount;

            $lista_promo[] = $record;

        }

        return $lista_promo;

    }

    private function getCustomerInfoRecord($order) {
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();

        $codice_cliente = $order->getCustomerId();

        //inizio - Modifica per codice cliente per mettere lo stesso di SG
        $customerTmpHelper = Mage::getModel('customer/customer');
        $customerTmp = $customerTmpHelper->load($order->getCustomerId());
        $sg_user_id = $customerTmp->getData('sg_user_id');
        //if (!$sg_user_id) $sg_user_id = $codice_cliente;
        //$codice_cliente = $sg_user_id;
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


        //$record->nome_cognome = $rag_sociale_nome." ".$rag_sociale_cognome;

        $customerId = $order->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $record->email = $customer->getEmail();
        $record->nome_cognome = $customer->getName();  // RINO 22/09/2016
        $record->coin_card = $order_custom_attributes['loyaltyCard'];
        $record->cap = $cap;

        return $record;

    }

    public function getOrderInfoRecord($order) {
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();
        $newDate_ordine = date("d/m/Y", strtotime($order->getDwOrderDatetime()));
        $scontrino = $order->getData('bill_number');
        $data_documento = $order->getData('bill_date');

        $fattura = $order->getData('invoice_number');
        $data_documento_fattura = $order->getData('invoice_date');

        $record = new stdClass();
        $record->data_ordine = $newDate_ordine;

        $record->destinazione = $order->getShippingDescription() == 'ClickAndCollect' ? "Negozio" : "Cliente";
        $record->tipo_trx ="V";
        $record->numero_scontrino = $scontrino;
        $record->data_scontrino = $data_documento;
        $record->numero_fattura = $fattura;
        $record->numero_ordine = ltrim($order->getDwOrderNumber(),'0');
        $record->sorgente = $order_custom_attributes['deviceCode'];
        $record->store_code_pick = $order->getData('store_code_pick');
        $record->shipping_method_dw = $order->getData('shipping_method_dw');

        $country_details=$this->getCountryDetails($order);  //RINO 14/09/2016
        $country_iva= $country_details->iva;                //RINO 14/09/2016
        $country_id = $country_details->country_id;         //RINO 14/09/2016

        $record->country_id = strtoupper($country_id);




        $billingAddress = $order->getBillingAddress();
        $billingAddressCountryid = strtolower($billingAddress->getCountryId());
        $orderCountry = CountryDBHelper::getCountryDetails($billingAddressCountryid);
        $record->codice_ente = $orderCountry->codice_ente;

        if ($fattura)
            $record->numero_fattura = $record->codice_ente."0/".$orderCountry->registro_iva.str_pad($fattura,7,'0',STR_PAD_LEFT);

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
        $orderValue = $order->getBaseGrandTotal();
        $orderValue_fmt = str_pad(str_replace('.','', $orderValue),7,'0',STR_PAD_LEFT);

        $record = new stdClass();
        if ($payment_method_selected=='ccsave') {
            //$txr_tender = new ItemTenderRecord('CC',$payment->getCcType(), $orderValue_fmt);
            $record->tipo_pagamento = 'CC';
        }elseif ($payment_method_selected=='cashondelivery') {
            //$txr_tender = new ItemTenderRecord('CO','', $orderValue_fmt);
            $record->tipo_pagamento = 'CO';
        }
        else {
            //PayPal
            //$txr_tender = new ItemTenderRecord('PP','',$orderValue_fmt );
            $record->tipo_pagamento = 'PP';
        }

        return $record;

    }

    private function getCountryIva($order) {     //RINO 13/09/2016

        $billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());
        $billing_country = $billingAddress->getData('country_id');
        $country_details = CountryDBHelper::getCountryDetails($billing_country);
        $country_iva= $country_details->iva;
        return $country_iva;
    }

    private function getCountryDetails($order) {     //RINO 13/09/2016

        $billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());
        $billing_country = $billingAddress->getData('country_id');
        $country_details = CountryDBHelper::getCountryDetails($billing_country);
        return $country_details;
    }

    private function getOrderLinesRecord($order) {
        //ITEM_STOCK
        $increment_id = $order->getIncrementId();
        $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);


        $country_details=$this->getCountryDetails($order);  //RINO 14/09/2016
        $country_iva= $country_details->iva;                //RINO 14/09/2016
        $country_id = $country_details->country_id;         //RINO 14/09/2016

        $lista_record = array();
        foreach ($lines as $line) {
            $record = new stdClass();
            //print_r($line);
            $sku = $line['sku'];
            $qty = $line['order_quantity'];

            $unit_price = $line['unit_price'];
            $discount_value = $line['discount_value'];
            $item_dw_promo_id = $line['item_dw_promo_id'];
            $original_discount = $line['original_discount'] / $country_iva;

            //$stockRecord = new ItemStockRecord($sku, $unit_price, $qty, $discount_value, $item_dw_extra_points, $item_dw_return_points, $item_dw_promo_id);
            //array_push($lista_record, $stockRecord);
            $record->sku = $sku;
            $record->descrizione = $line['description'];
            $record->qta = $qty;
            //$record->prezzo_cliente = $unit_price * $country_iva;
            $record->prezzo_cliente = $unit_price;
            //$record->sconto_linea = $original_discount !=0 ? $original_discount : null;
            $record->sconto_linea = $line['discount_value'];
            $record->codice_promo = $item_dw_promo_id;

            //$imponibile = ($unit_price * $record->qta) - $original_discount;                  // RINO 13/09/2016
            //$totale = $imponibile * $country_iva;                                               // RINO 13/09/2016
            //$iva = $totale - $imponibile;                                                       // RINO 13/09/2016

            $row_amount = number_format($record->prezzo_cliente * $record->qta,2) - $discount_value;

            $imponibile_tmp = $row_amount/1.22;

            $imponibile = number_format(round($imponibile_tmp,2),2);

            $iva = $row_amount - $imponibile;

            $record->totale_netto = $imponibile;
            $record->iva = $iva;
            //$record->totale = $totale;
            $record->totale = $row_amount;

            $record->country_id=$country_id;

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

        $country_details=$this->getCountryDetails($order);  //RINO 14/09/2016
        $country_iva= $this->getCountryIva($order);         //RINO 13/09/2016
        $country_id = $country_details->country_id;         //RINO 14/09/2016

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
            $record->sconto_linea = $line->discount_value;
            $record->codice_promo = '';

            $imponibile = ($qty * $line->base_price) +  $line->discount_value;
            $row_amount = $imponibile * $country_iva;
            $iva = $row_amount - $imponibile;

            $record->totale_netto = $imponibile;
            $record->iva = $iva;
            $record->totale = $row_amount;

            $record->country_id=$country_id;

            array_push($lista_record, $record);


        }

        return $lista_record;

    }

    /*private function getItemFeeRecord($order) {
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

                foreach ($options as $option) {
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
                }

            }
        }

        return $lista_record;

    }
    */

    /*private function generateBillReport($lista_ordini, $lista_creditmemo) {
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
            $record_merchandize_promo_lista = $this->getMerchandizePromo($order);
            $record_discount_promo = $this->getDiscountRecord($order);
            $lista_item_fee = $this->getItemFeeRecord($order);
            $record_spedizioni = $this->getRecordSpedizioni($order);
            $lista_righe_ordine = $this->getOrderLinesRecord($order);

            //Crea record finale

            //SPEDIZIONE
            $record = new stdClass();
            $record->codice_cliente=$record_customer_info->codice_cliente;
            $record->nome_cognome=$record_customer_info->nome_cognome;
            $record->email=$record_customer_info->email;
            $record->web_user_id=$record_customer_info->web_user_id;
            $record->coin_card=$record_customer_info->coin_card;
            $record->numero_ordine=$record_order_info->numero_ordine;
            $record->sorgente=$record_order_info->sorgente;
            $record->destinazione="Cliente";
            $record->data_ordine=$record_order_info->data_ordine;
            $record->tipo_trx="V";
            $record->numero_scontrino=$record_order_info->numero_scontrino;
            $record->data_scontrino=$record_order_info->data_scontrino;
            $record->numero_fattua=$record_order_info->numero_fattura;
            $record->sku = $record_spedizioni->sku;
            $record->descrizione = '';
            $record->qta = '';
            $record->prezzo_cliente = '';
            $record->sconto_linea = 0;
            $record->codice_promozione = $record_spedizioni->codice_promozione;
            $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
            $record->totale_netto = $record_spedizioni->totale_netto;
            $record->iva = $record_spedizioni->iva;
            $record->totale = $record_spedizioni->totale;
            $record->metodo_spedizione = "Standard";
            $record->punti_resi = "";
            $record->punti_guadagnati = $record_spedizioni->punti_guadagnati;
            $record->punti_spesi = $record_spedizioni->punti_spesi;
            $record->spese_spedizione = $record_spedizioni->spese_spedizione;
            $record->cap = $record_customer_info->cap;
            $record->store_code = $this->config->getEcommerceShopCode();
            array_push($lista_record, $record);

            //PROMOZIONE
            foreach ($record_merchandize_promo_lista as $record_merchandize_promo) {
                $record = new stdClass();
                $record->codice_cliente=$record_customer_info->codice_cliente;
                $record->nome_cognome=$record_customer_info->nome_cognome;
                $record->email=$record_customer_info->email;
                $record->web_user_id=$record_customer_info->web_user_id;
                $record->coin_card=$record_customer_info->coin_card;
                $record->numero_ordine=$record_order_info->numero_ordine;
                $record->sorgente=$record_order_info->sorgente;
                $record->destinazione="Cliente";
                $record->data_ordine=$record_order_info->data_ordine;
                $record->tipo_trx="V";
                $record->numero_scontrino=$record_order_info->numero_scontrino;
                $record->data_scontrino=$record_order_info->data_scontrino;
                $record->numero_fattua=$record_order_info->numero_fattura;
                $record->sku = $record_merchandize_promo->sku;
                $record->descrizione = $record_merchandize_promo->descrizione;
                $record->qta = $record_merchandize_promo->qta;
                $record->prezzo_cliente = $record_merchandize_promo->prezzo_cliente;
                $record->sconto_linea = 0;
                $record->codice_promozione = $record_merchandize_promo->codice_promozione;
                $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
                $record->totale_netto = $record_merchandize_promo->totale_netto;
                $record->iva = $record_merchandize_promo->iva;
                $record->totale = $record_merchandize_promo->totale;
                $record->metodo_spedizione = "Standard";
                $record->punti_resi = "";
                $record->punti_guadagnati = '';
                $record->punti_spesi = '';
                $record->spese_spedizione = '';
                $record->cap = $record_customer_info->cap;
                $record->store_code = $this->config->getEcommerceShopCode();
                array_push($lista_record, $record);
            }

            //RIGHE ORDINE
            foreach ($lista_righe_ordine as $record_riga) {
                $record = new stdClass();
                $record->codice_cliente=$record_customer_info->codice_cliente;
                $record->nome_cognome=$record_customer_info->nome_cognome;
                $record->email=$record_customer_info->email;
                $record->web_user_id=$record_customer_info->web_user_id;
                $record->coin_card=$record_customer_info->coin_card;
                $record->numero_ordine=$record_order_info->numero_ordine;
                $record->sorgente=$record_order_info->sorgente;
                $record->destinazione="Cliente";
                $record->data_ordine=$record_order_info->data_ordine;
                $record->tipo_trx="V";
                $record->numero_scontrino=$record_order_info->numero_scontrino;
                $record->data_scontrino=$record_order_info->data_scontrino;
                $record->numero_fattua=$record_order_info->numero_fattura;
                $record->sku = $record_riga->sku;
                $record->descrizione = $record_riga->descrizione;
                $record->qta = $record_riga->qta;
                $record->prezzo_cliente = $record_riga->prezzo_cliente;
                $record->sconto_linea = $$record_riga->sconto_linea;
                $record->codice_promozione = "";
                $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
                $record->totale_netto = $record_riga->totale_netto;
                $record->iva = $record_riga->iva;
                $record->totale = $record_riga->totale;
                $record->metodo_spedizione = "Standard";
                $record->punti_resi = "";
                $record->punti_guadagnati = '';
                $record->punti_spesi = '';
                $record->spese_spedizione = '';
                $record->cap = $record_customer_info->cap;
                $record->store_code = $this->config->getEcommerceShopCode();

                array_push($lista_record, $record);
            }

        } //end for

         // SEZIONE CREDITMEMO

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
            $codice_cliente_dw = $order->getDwCustomerId();
            $order_no = $order->getDwOrderNumber();
            $data_ordine = $order->getCreatedAt();
            $newDate_ordine = date("d/m/Y H:i:s", strtotime($order->getDwOrderDatetime()));

            $trx_date = $paymentObj->timestamp;


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
                $trx_discount = new TrxDiscountRecord($valore, $promoObj->promotion_id);
                array_push($lista_record, $trx_discount);
            }

            //ITEM_STOCK
            //$lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
            $lines = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);

            foreach ($lines as $line) {
                //print_r($line);
                $sku = $line->sku;
                $qty = -1 * $line->qty;

                $unit_price = str_pad(str_replace('.','', $line->base_price),7,'0',STR_PAD_LEFT);
                $discount_value = str_pad(str_replace('.','', 0),7,'0',STR_PAD_LEFT);
                $item_dw_promo_id = '';
                $item_dw_extra_points = '';
                $item_dw_return_points = '';

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
            $promoObj = $orderDbHelper->getShippingPromotion();
            $shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);
            $shippingAmount_fmt = str_pad(str_replace('.','', $shippingAmount),7,'0',STR_PAD_LEFT);
            $shippingDiscount = number_format(($promoObj->value * -1),2);
            $shippingDiscount_fmt = str_pad(str_replace('.','', $shippingDiscount),7,'0',STR_PAD_LEFT);
            $trx_discount = new ItemFeeRecord('Shipping Charges', $shippingAmount_fmt, $shippingDiscount_fmt, $promoObj->promotion_id);
            array_push($lista_record, $trx_discount);

            //ITEM_TENDER
            $payment = $order->getPayment();
            $payment_method_selected = $payment->getMethod();
            //print_r($payment->getData());
            //echo "\nPayment Method: ".$payment_method_selected;
            $orderValue = number_format($info_creditmemo->grand_total,2);
            $orderValue_fmt = "-".str_pad(str_replace('.','', $orderValue),7,'0',STR_PAD_LEFT);
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
    }*/

    private function generateReport($lista_ordini, $lista_creditmemo) {
        $lista_record = array();
        $start_date = date('d/m/Y H:i:s');
        //$record = new RegisterOpenRecord($this->config->getEcommerceShopCode(), $start_date);
        //$codice_cassa = $this->config->getEcommerceShopCodiceCassa();


        foreach ($lista_ordini as $increment_id) {
            $record = new stdClass();

            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');

            $record_customer_info = $this->getCustomerInfoRecord($order);
            $record_order_info = $this->getOrderInfoRecord($order);
            $record_payment_info = $this->getPaymentInfoRecord($order);
            $record_merchandize_promo_lista = $this->getMerchandizePromo($order);

            //$lista_item_fee = $this->getItemFeeRecord($order);
            $record_spedizioni = $this->getRecordSpedizioni($order);
            $lista_righe_ordine = $this->getOrderLinesRecord($order);

            //Crea record finale

            //SPEDIZIONE
            $record = new stdClass();
            $record->codice_cliente=$record_customer_info->codice_cliente;
            $record->nome_cognome=$record_customer_info->nome_cognome;
            $record->email=$record_customer_info->email;
            $record->web_user_id=$record_customer_info->web_user_id;
            $record->coin_card=$record_customer_info->coin_card;
            $record->numero_ordine=$record_order_info->numero_ordine;
            $record->sorgente=$record_order_info->sorgente;
            $record->destinazione=$record_order_info->destinazione;
            $record->data_ordine=$record_order_info->data_ordine;
            $record->tipo_trx="V";
            $record->numero_scontrino=$record_order_info->numero_scontrino;
            $record->data_scontrino=$record_order_info->data_scontrino;
            $record->numero_fattura=$record_order_info->numero_fattura;
            $record->sku = $record_spedizioni->sku;
            $record->descrizione = '';
            $record->qta = '';
            $record->prezzo_cliente = '';
            $record->sconto_linea = $record_spedizioni->sconto_spese_spedizione;
            $record->codice_promozione = $record_spedizioni->codice_promozione;
            $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
            $record->totale_netto = $record_spedizioni->totale_netto;
            $record->iva = $record_spedizioni->iva;
            $record->totale = $record_spedizioni->totale;
            $record->metodo_spedizione = $record_order_info->shipping_method_dw;
            if ($record_order_info->destinazione== "Negozio") $record->metodo_spedizione = "ClickAndCollect";
            $record->punti_resi = "";
            $record->punti_guadagnati = $record_spedizioni->punti_guadagnati;
            $record->punti_spesi = $record_spedizioni->punti_spesi;
            $record->spese_spedizione = $record_spedizioni->spese_spedizione;
            $record->cap = $record_customer_info->cap;
            //$record->store_code = $this->config->getEcommerceShopCode();
            $record->store_code=$record_order_info->store_code_pick;
            $record->country_id = $record_order_info->country_id;
            array_push($lista_record, $record);

            //PROMOZIONE
            foreach ($record_merchandize_promo_lista as $record_merchandize_promo) {
                $record = new stdClass();
                $record->codice_cliente=$record_customer_info->codice_cliente;
                $record->nome_cognome=$record_customer_info->nome_cognome;
                $record->email=$record_customer_info->email;
                $record->web_user_id=$record_customer_info->web_user_id;
                $record->coin_card=$record_customer_info->coin_card;
                $record->numero_ordine=$record_order_info->numero_ordine;
                $record->sorgente=$record_order_info->sorgente;
                $record->destinazione=$record_order_info->destinazione;
                $record->data_ordine=$record_order_info->data_ordine;
                $record->tipo_trx="V";
                $record->numero_scontrino=$record_order_info->numero_scontrino;
                $record->data_scontrino=$record_order_info->data_scontrino;
                $record->numero_fattura=$record_order_info->numero_fattura;
                $record->sku = $record_merchandize_promo->sku;
                $record->descrizione = $record_merchandize_promo->descrizione;
                $record->qta = $record_merchandize_promo->qta;
                $record->prezzo_cliente = $record_merchandize_promo->prezzo_cliente;
                $record->sconto_linea = 0;
                $record->codice_promozione = $record_merchandize_promo->codice_promozione;
                $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
                $record->totale_netto = $record_merchandize_promo->totale_netto;
                $record->iva = $record_merchandize_promo->iva;
                $record->totale = $record_merchandize_promo->totale;
                $record->metodo_spedizione = $record_order_info->shipping_method_dw;
                if ($record_order_info->destinazione== "Negozio") $record->metodo_spedizione = "ClickAndCollect";
                $record->punti_resi = "";
                $record->punti_guadagnati = '';
                $record->punti_spesi = '';
                $record->spese_spedizione = '';
                $record->cap = $record_customer_info->cap;
                //$record->store_code = $this->config->getEcommerceShopCode();
                $record->store_code=$record_order_info->store_code_pick;
                $record->country_id = $record_order_info->country_id;
                array_push($lista_record, $record);
            }

            //RIGHE ORDINE
            foreach ($lista_righe_ordine as $record_riga) {
                $record = new stdClass();
                $record->codice_cliente=$record_customer_info->codice_cliente;
                $record->nome_cognome=$record_customer_info->nome_cognome;
                $record->email=$record_customer_info->email;
                $record->web_user_id=$record_customer_info->web_user_id;
                $record->coin_card=$record_customer_info->coin_card;
                $record->numero_ordine=$record_order_info->numero_ordine;
                $record->sorgente=$record_order_info->sorgente;
                $record->destinazione=$record_order_info->destinazione;
                $record->data_ordine=$record_order_info->data_ordine;
                $record->tipo_trx="V";
                $record->numero_scontrino=$record_order_info->numero_scontrino;
                $record->data_scontrino=$record_order_info->data_scontrino;
                $record->numero_fattura=$record_order_info->numero_fattura;
                $record->sku = $record_riga->sku;
                $record->descrizione = $record_riga->descrizione;
                $record->qta = $record_riga->qta;
                $record->prezzo_cliente = $record_riga->prezzo_cliente;
                $record->sconto_linea = $record_riga->sconto_linea;
                $record->codice_promozione = $record_riga->codice_promo;
                $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
                $record->totale_netto = $record_riga->totale_netto;
                $record->iva = $record_riga->iva;
                $record->totale = $record_riga->totale;
                $record->metodo_spedizione = $record_order_info->shipping_method_dw;
                if ($record_order_info->destinazione== "Negozio") $record->metodo_spedizione = "ClickAndCollect";
                $record->punti_resi = "";
                $record->punti_guadagnati = '';
                $record->punti_spesi = '';
                $record->spese_spedizione = '';
                $record->cap = $record_customer_info->cap;
                //$record->store_code = $this->config->getEcommerceShopCode();
                $record->store_code=$record_order_info->store_code_pick;
                $record->country_id = strtoupper($record_riga->country_id);

                array_push($lista_record, $record);
            }

        } //end for

        //Gestione RESI

        foreach ($lista_creditmemo as $obj) {
            //echo "\nCreditMemo: ".$obj->creditmemo_id;
            $order_id = $obj->order_id;
            $creditmemo_id = $obj->creditmemo_id;
            $info_creditmemo = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);
            //print_r($info_creditmemo);



            $order = Mage::getModel('sales/order')->load($order_id);

            $record_customer_info = $this->getCustomerInfoRecord($order);
            $record_order_info = $this->getOrderInfoRecord($order);
            $record_payment_info = $this->getPaymentInfoRecord($order);
            $record_merchandize_promo_lista = $this->getMerchandizePromo($order);
            //$record_discount_promo = $this->getDiscountRecord($order);
            //$lista_item_fee = $this->getItemFeeRecord($order);
            $record_spedizioni = $this->getRecordSpedizioni($order);
            $lista_righe_creditmemo = $this->getCreditMemoLinesRecord($creditmemo_id,$order);
            //print_r($lista_righe_creditmemo);
            //Crea record finale

            //RIGHE RESO ORDINE
            foreach ($lista_righe_creditmemo as $record_riga) {
                $record = new stdClass();
                $record->codice_cliente=$record_customer_info->codice_cliente;
                $record->nome_cognome=$record_customer_info->nome_cognome;
                $record->email=$record_customer_info->email;
                $record->web_user_id=$record_customer_info->web_user_id;
                $record->coin_card=$record_customer_info->coin_card;
                $record->numero_ordine=$record_order_info->numero_ordine;
                $record->sorgente=$record_order_info->sorgente;
                $record->destinazione=$record_order_info->destinazione;
                $record->data_ordine=$record_order_info->data_ordine;
                $record->tipo_trx="R";
                $record->numero_scontrino=$info_creditmemo->bill_number;
                $record->data_scontrino=$info_creditmemo->bill_date;
                $record->numero_fattua=$info_creditmemo->invoice_number;
                $record->sku = $record_riga->sku;
                $record->descrizione = $record_riga->descrizione;
                $record->qta = $record_riga->qta;
                $record->prezzo_cliente = $record_riga->prezzo_cliente;
                $record->sconto_linea = $record_riga->sconto_linea;
                $record->codice_promozione = "";
                $record->tipo_pagamento = $record_payment_info->tipo_pagamento;
                $record->totale_netto = $record_riga->totale_netto;
                $record->iva = $record_riga->iva;
                $record->totale = $record_riga->totale;
                $record->metodo_spedizione = $record_order_info->shipping_method_dw;
                if ($record_order_info->destinazione== "Negozio") $record->metodo_spedizione = "ClickAndCollect";
                $record->punti_resi = "";
                $record->punti_guadagnati = '';
                $record->punti_spesi = '';
                $record->spese_spedizione = '';
                $record->cap = $record_customer_info->cap;
                //$record->store_code = $this->config->getEcommerceShopCode();
                $record->country_id = strtoupper($record_riga->country_id);


                array_push($lista_record, $record);
            }

        } //end for
        return $lista_record;
    }
        private function writeRecordToFile($lista_record) {


            $path_template = realpath(dirname(__FILE__)) .'/template';
            $excel = PHPExcel_IOFactory::createReader('Excel2007');
            $excel = $excel->load($path_template."/dettaglio_vendite.xlsx");
            $excel->setActiveSheetIndex(0);

            $objWorksheet = $excel->getActiveSheet();
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN
                    )
                )
            );

            $cella = "AH4";
            $xfIndex = $objWorksheet->getCell('A4')->getXfIndex();
            $objWorksheet->getCell($cella)->setValue("Paese Spedizione");
            $objWorksheet->getCell($cella)->setXfIndex($xfIndex);


            $riga = 5;
            foreach ($lista_record as $record) {
                $cella = "A".$riga;
                $xfIndex = $objWorksheet->getCell('A5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->codice_cliente);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

                $cella = "B".$riga;
                /*
                $cella_estesa = 'B'.$riga.':C'.$riga;
                $objWorksheet->mergeCells($cella_estesa);
                $xfIndex = $objWorksheet->getCell('B5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->nome_cognome);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $objWorksheet->getStyle($cella_estesa)->applyFromArray($styleArray);
                */

                $cella = "B".$riga;
                $xfIndex = $objWorksheet->getCell('B5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->nome_cognome);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

                $cella = "C".$riga;
                $xfIndex = $objWorksheet->getCell('C5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue(' ');
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

                $cella = "D".$riga;
                $xfIndex = $objWorksheet->getCell('D5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->email);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "E".$riga;
                $xfIndex = $objWorksheet->getCell('E5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->web_user_id);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "F".$riga;
                $xfIndex = $objWorksheet->getCell('F5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->coin_card);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "G".$riga;
                $xfIndex = $objWorksheet->getCell('G5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->numero_ordine);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "H".$riga;
                $xfIndex = $objWorksheet->getCell('H5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->sorgente);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "I".$riga;
                $xfIndex = $objWorksheet->getCell('I5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->destinazione);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "J".$riga;
                $xfIndex = $objWorksheet->getCell('J5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->data_ordine);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "K".$riga;
                $xfIndex = $objWorksheet->getCell('K5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->tipo_trx);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $objWorksheet->getStyle($cella)->applyFromArray($styleArray);
                $cella = "L".$riga;
                $xfIndex = $objWorksheet->getCell('L5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->numero_scontrino);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "M".$riga;
                $xfIndex = $objWorksheet->getCell('M5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->data_scontrino);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "N".$riga;
                $xfIndex = $objWorksheet->getCell('N5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->numero_fattura);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "O".$riga;
                $xfIndex = $objWorksheet->getCell('O5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->sku);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "P".$riga;
                $xfIndex = $objWorksheet->getCell('P5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->descrizione);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "Q".$riga;
                $xfIndex = $objWorksheet->getCell('Q5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->qta);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "R".$riga;
                $xfIndex = $objWorksheet->getCell('R5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->prezzo_cliente);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
                //$objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);
                $cella = "S".$riga;
                $xfIndex = $objWorksheet->getCell('S5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->sconto_linea);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $cella = "T".$riga;
                $xfIndex = $objWorksheet->getCell('T5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->codice_promozione);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "U".$riga;
                $xfIndex = $objWorksheet->getCell('U5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->tipo_pagamento);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "V".$riga;
                $xfIndex = $objWorksheet->getCell('V5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->totale_netto);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

                $cella = "W".$riga;
                $xfIndex = $objWorksheet->getCell('W5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->iva);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
                $cella = "X".$riga;
                $xfIndex = $objWorksheet->getCell('X5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->totale);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $objWorksheet->getStyle($cella)->getNumberFormat()->setFormatCode('#,##0.00');
                /*
                $cella = "Y".$riga;
                $cella_estesa = 'Y'.$riga.':Z'.$riga;
                $objWorksheet->mergeCells($cella_estesa);
                $xfIndex = $objWorksheet->getCell('Y5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->metodo_spedizione);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                */

                $cella = "Y".$riga;
                $xfIndex = $objWorksheet->getCell('Y5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->metodo_spedizione);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

                $cella = "Z".$riga;
                $xfIndex = $objWorksheet->getCell('Z5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue(' ');
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

                $cella = "AA".$riga;
                $objWorksheet->getStyle($cella_estesa)->applyFromArray($styleArray);
                $xfIndex = $objWorksheet->getCell('AA5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->punti_resi);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "AB".$riga;
                $xfIndex = $objWorksheet->getCell('AB5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->punti_guadagnati);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "AC".$riga;
                $xfIndex = $objWorksheet->getCell('AC5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->punti_spesi);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "AD".$riga;
                $xfIndex = $objWorksheet->getCell('AD5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->spese_spedizione);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "AE".$riga;
                $xfIndex = $objWorksheet->getCell('AE5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->sconto_spese_spedizione);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "AF".$riga;
                $xfIndex = $objWorksheet->getCell('AF5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->cap);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "AG".$riga;
                $xfIndex = $objWorksheet->getCell('AG5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue(ltrim($record->store_code),'0');
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $cella = "AH".$riga;
                $xfIndex = $objWorksheet->getCell('AG5')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->country_id);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);

                $riga++;
            }


            $timestamp = date('Ymd');
            $start_date_fmt = date('d/m/Y', strtotime($this->start_date));
            $end_date_fmt = date('d/m/Y', strtotime($this->end_date));

            $cella = "C2";

            $objWorksheet->getCell($cella)->setValue("Data: da ".$start_date_fmt." a ".$end_date_fmt);

            $cella = "A2";
            //$tmp_date = date("d/m/Y ", strtotime($this->start_date));
            $objWorksheet->getCell($cella)->setValue("Da: ".$start_date_fmt);
            $cella = "A3";
            //$tmp_date = date("d/m/Y ", strtotime($this->end_date));
            $objWorksheet->getCell($cella)->setValue(" a: ".$end_date_fmt);


            $file_name = "REPORT_VENDUTO_".$timestamp.".xlsx";
            $directory = '/tmp';
            $full_name = $directory."/".$file_name;

            $objWriter = PHPExcel_IOFactory::createWriter($excel,'Excel2007');
            $objWriter->save($full_name);

            //CREA HTML
            $objWriter = new PHPExcel_Writer_HTML($excel);
            $html_file_name=  $directory."/REPORT_VENDUTO_".$timestamp.".html";
            $objWriter->save($html_file_name);


            //CREA PDF
            /*$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
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
            $pdf_file_name=  $directory."/REPORT_VENDUTO_".$timestamp.".pdf";
            $objWriter->save($pdf_file_name);*/

            echo "\nInvio Email";
            $this->inviaEmail($full_name);



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

        // $sql ="SELECT increment_id FROM sales_flat_order WHERE str_to_date(CONCAT(bill_date,' 00:00:00') ,'%d/%m/%Y %H:%i:%s') BETWEEN '$start' AND '$end'
        //  AND (status='$status' or status='closed')";

        $sql ="SELECT increment_id FROM sales_flat_order WHERE str_to_date(CONCAT(created_at,' 00:00:00') ,'%Y-%m-%d %H:%i:%s') BETWEEN '$start' AND '$end'
          AND (status='$status' or status='closed')";

         //TEST
         /*$sql ="SELECT increment_id FROM sales_flat_order WHERE dw_order_number='00274823'
         AND (status='$status' or status='closed')";*/

        /*$sql ="SELECT increment_id FROM sales_flat_order WHERE dw_order_number='00000000'
         AND (status='$status' or status='closed')";*/
        // END TEST



        echo "\n".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $lista[] = $row->increment_id;
        }
        OMDBManager::closeConnection($con);

        $this->log->LogDebug("Record trovati:");
        //print_r($lista);
        return $lista;
    }

    private function getListaCreditMemoDaExportare($start = null, $end = null) {

        //$t = new CreditMemoHelper();
        //$lista = $t->getListaCreditMemoExportare($start, $end);


        $con = OMDBManager::getMagentoConnection();

        //$sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE bill_date BETWEEN '$start' AND '$end'";                        //RINO 03/09/2016
        //$sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE str_to_date(CONCAT(bill_date,' 00:00:00') ,'%d/%m/%Y %H:%i:%s') BETWEEN '$start' AND '$end'";
        $sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE str_to_date(CONCAT(created_at,' 00:00:00') ,'%Y-%m-%d %H:%i:%s') BETWEEN '$start' AND '$end'";
        echo "\n".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $obj = new stdClass();
            $obj->creditmemo_id = $row->entity_id;
            $obj->order_id = $row->order_id;
            $lista[] = $obj;
        }
        OMDBManager::closeConnection($con);

        // TEST
        /*$obj = new stdClass();
        $obj->creditmemo_id = 290;
        $obj->order_id = 62897;
        $lista[] = $obj;*/
        // END TEST

        return $lista;
    }

    private function inviaEmail($nome_file, $pdf_file_name = null) {

        //return;

        //info ordine
        $invoicepath = $nome_file;

        $message = "Report Automatico";

        $email_array=array("nomovs@gmail.com");

        $mail = new PHPMailer;
        $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
        $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
        $mail->From 	= 'noreply@ovs.it';
        $mail->FromName = 'OVS Online Store';
        $mail->Subject 	= 'Report Automatico: Dettaglio Vendite';
        $mail->Body		= $message;

        $mail->addAttachment($invoicepath);
        if ($pdf_file_name)
            $mail->addAttachment($pdf_file_name);

        $mail->isHTML(false);
        foreach($email_array as $email_addr) {
            $mail->addAddress($email_addr);
        }
        $mail->send();

    }


}

//TODO METTERE LA DATA AUTOMATICA
$t = new BillExport();

$date= date('Y-m-d');
$start_date = date('Y-m-d 00:00:00', strtotime('-8 day', strtotime($date)));
$end_date = date('Y-m-d 23:59:59', strtotime('-1 day', strtotime($date)));


//$start_date="2016-10-01 00:00:00";
//$end_date="2016-10-11 23:59:59";

$t->export($start_date, $end_date);
