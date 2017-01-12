<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 11/07/15
 * Time: 11:10
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
require_once realpath(dirname(__FILE__)) . "/../Utils/pdf/dompdf_config.inc.php";
require_once realpath(dirname(__FILE__)) . "/../Utils/mailer/PHPMailerAutoload.php";
require_once realpath(dirname(__FILE__))."/../omdb/ShipmentDBHelperDM.php";
require_once realpath(dirname(__FILE__))."/../omdb/CountryDBHelper.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();

class ConfirmOrder {

    private $status_to_export = "pending";

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/confirm_order.log',KLogger::DEBUG);

    }

    /**
     * Inizia export flusso invio ordini
     */
    public function export($lista_ordini) {

        $this->log->LogInfo("Start");

        $lista_ordini = $this->getListaConfermaOrdine();

        if ($lista_ordini) {
            $this->process($lista_ordini);
        }
        else
            $this->log->LogInfo("\nNessun ordine da esportare");

    }

    private function getInfoCliente($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();

        return $order->getBillingAddress();

    }


    private function getInfoOrdine($increment_id, $delivery_id) {
        echo "\nProcessing: ".$increment_id.", delivery_id:".$delivery_id;
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $order_customer_locale=$order->getCustomerLocale();
        $ocl=substr($order_customer_locale,0,2);

        // print_r($order->getData());
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();

        $shippingDBHelper = new ShipmentDBHelperDM($order->getDwOrderNumber());
        $shipping_custom_attributes = $shippingDBHelper->getCustomAttributes();


        //$piva = $order_custom_attributes['partitaIva'] ? "IT".$order_custom_attributes['partitaIva']: "";
        $piva = $order_custom_attributes['partitaIva'] ? $order_custom_attributes['partitaIva']: "";
        $cf = $order_custom_attributes['codiceFiscale'];

        $bill_to_info= $order->getBillingAddress();

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

        $codice_cliente_dw = $order->getDwCustomerId();
        $order_no = $order->getDwOrderNumber();
        $newDate_ordine = date("d/m/Y", strtotime($order->getDwOrderDatetime()));

        $scontrino = $order->getData('bill_number');
        $data_documento = $order->getData('bill_date');

        $fattura = $order->getData('invoice_number');
        $data_documento_fattura = $order->getData('invoice_date');

        $customerId = $order->getCustomerId();

        $billing_address= $order->getBillingAddress();

        $infoOrdine = new stdClass();
        $infoOrdine->cliente = $codice_cliente_dw;
        $infoOrdine->scontrino = $scontrino;
        $infoOrdine->data_documento = $data_documento;
        $infoOrdine->ordine = $order_no;
        $infoOrdine->data_ordine = $newDate_ordine;
        $infoOrdine->telefono = $billing_address->getTelephone();

        //27-10-2016
        $customer_email = $order->getData('customer_email');
        $this->log->LogDebug("Invio conferma ordine: ".$increment_id.", al cliente: ".$customer_email);
        if (!$customer_email) {
            $this->log->LogDebug("Email non trovata conferma ordine: ".$increment_id.", al cliente: ".$customer_email);
            $w = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sql = "SELECT * FROM sales_flat_order WHERE entity_id=".$order->getId();
            $res = $w->query($sql);
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $customer_email = $row['customer_email'];
            $this->log->LogDebug("Email cercata sul db conferma ordine: ".$increment_id.", al cliente: ".$customer_email);
        }
        //$infoOrdine->email = $order->getData('customer_email');
        $infoOrdine->email = $customer_email;


        $infoOrdine->num_fattura = $fattura;
        $infoOrdine->data_documento_fattura = $data_documento_fattura;
        $infoOrdine->need_invoice = $order->getData('needInvoice');
        $infoOrdine->rag_sociale_nome = $rag_sociale_nome;
        $infoOrdine->rag_sociale_cognome = $rag_sociale_cognome;
        $infoOrdine->piva = $piva;
        $infoOrdine->cf = $cf;


        $infoFiscale = $this->getInfoFiscale($increment_id, $ocl);

        $infoOrdine->amount = $order->getBaseGrandTotal();
        //echo "\nAmount: ".$infoOrdine->amount;
        // $imponibile_tmp = $order->getBaseGrandTotal()/1.22;                      // RINO 30/07/2016 RINO 8/07/2017 generalizzazione per iva paese
        // $imponibile_tmp = $order->getBaseGrandTotal() - $order->getBaseTaxAmount(); // RINO 30/07/2016
        $imponibile_tmp = round($order->getBaseGrandTotal()/ $infoFiscale->iva,2);
        //echo "\nImponibile tmp: ".$imponibile_tmp;
        $infoOrdine->imponibile = number_format(round($imponibile_tmp,2),2);
        //echo "\nImponibile: ".$infoOrdine->imponibile;
        //$infoOrdine->iva = $infoOrdine->amount - $infoOrdine->imponibile;         // RINO 30/07/2016
        $infoOrdine->iva = number_format(round($order->getBaseGrandTotal() - $infoOrdine->imponibile,2),2);    // RINO 30/07/2016


        if ($shipping_custom_attributes['clickAndCollectStoreId']) {
            //si tratta di click&collect
            $infoOrdine->click_collect = true;
            $infoOrdine->negozio = $shipping_custom_attributes['clickAndCollectStoreName'];
            $infoOrdine->indirizzo_negozio = $shipping_custom_attributes['clickAndCollectAddress1']." ".$shipping_custom_attributes['clickAndCollectAddress2'];
            $infoOrdine->negozio_paese = $shipping_custom_attributes['clickAndCollectPostalCode']." ".$shipping_custom_attributes['clickAndCollectCity']." ".$shipping_custom_attributes['clickAndCollectStateCode'];
            $infoOrdine->country = $shipping_custom_attributes['clickAndCollectCountryCode'];
            $infoOrdine->orario_negozio_html = $shipping_custom_attributes['clickAndCollectStoreHoursHtml'];
            $infoOrdine->orario_negozio = $shipping_custom_attributes['clickAndCollectStoreHours'];
            if (array_key_exists('clickAndCollectEmail', $shipping_custom_attributes ))
                $infoOrdine->email_negozio = $shipping_custom_attributes['clickAndCollectEmail'];
            else
                $infoOrdine->email_negozio = null;

        } else
            $infoOrdine->click_collect = false;

        //Tracking
        $order_shipping_address= $order->getShippingAddress();
        $country_id = $order_shipping_address->country_id;
        $infoOrdine->country_id = $country_id;


        if ($delivery_id) {
            $shipmentDbHelper = new ShipmentDBHelperDM($infoOrdine->ordine);
            $objShip = $shipmentDbHelper->getShipment($infoOrdine->ordine, $delivery_id); //in questo modo seleziono la delivery che mi interessa

            $infoOrdine->tracking_url=htmlspecialchars($objShip->first_track);
        } else
            $infoOrdine->tracking_url='';

        // barcode    // Rino 01/07/2016  verificare  la composizione da specifiche stargate
        $doc_number=$scontrino;
        if (strtolower($country_id)=='it') {
            $infoOrdine->barcode =
                "001" .
                str_pad(ltrim($doc_number, '0'), 5, '0', STR_PAD_LEFT) .
                date('d') .
                date('Hi') .
                str_pad($infoOrdine->amount * 100, 10, '0', STR_PAD_LEFT);
            /*"
            001
            14205
            29
            2151
            0000003696
            "*/
        }

        /**GESTIONE LINK sito OVS.it oppure OVSFASHION.COM**/

        if (strtoupper($country_id)=='IT') {
            $infoOrdine->link_sito = "http://www.ovs.it";
        } else {
            $infoOrdine->link_sito = "http://www.ovsfashion.com";
        }

       // print_r($infoOrdine);
        return $infoOrdine;

    }

    private function getInfoDestinatario($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $shipping_address= $order->getShippingAddress();
        return $shipping_address;
    }

    private function getInfoPromotionOLD($increment_id) {
        //TRX DISCOUNT
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $order_no = $order->getDwOrderNumber();

        $orderDbHelper = new OrderDBHelper($order_no);
        $promoObj = $orderDbHelper->getMerchandizePromotion();
        $lista_record = array();
        if (property_exists($promoObj, 'promotion_id')) {

            $valore_promo = number_format($promoObj->value * -1, 2);

            $trx_discount = new stdClass();
            $trx_discount->valore = $valore_promo;
            $trx_discount->promotion_id = $promoObj->promotion_id;
            $trx_discount->campaign_id = $promoObj->campaign_id ;
            array_push($lista_record, $trx_discount);
        }


        return $lista_record;

    }

    private function getInfoPromotionNew($increment_id) {
        //TRX DISCOUNT
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $order_no = $order->getDwOrderNumber();

        $orderDbHelper = new OrderDBHelper($order_no);
        $promoObjArray = $orderDbHelper->getMerchandizePromotion();
        $lista_record = array();
        foreach ($promoObjArray as $promoObj) {

            $valore_promo = number_format($promoObj->value * -1, 2);

            $trx_discount = new stdClass();
            $trx_discount->valore = $valore_promo;
            $trx_discount->promotion_id = $promoObj->promotion_id;
            $trx_discount->campaign_id = $promoObj->campaign_id ;
            array_push($lista_record, $trx_discount);
        }


        return $lista_record;

    }

    private function getOrderLinesOld($increment_id, $country) {  //RINO 07/09/2016) {

        $items = array();
        $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
        //print_r($lines);
        foreach ($lines as $line) {
            $codice = $line['sku'];
            $desc = $line['description'];
            $qty = $line['order_quantity'];
            $unit_price =  $line['unit_price'];
            $discount_value = $line['discount_value'];
            /*
            if ($discount_value=='0.00') $discount_value = $unit_price * $qty;
            $total = $discount_value;
            */
            if ($discount_value=='0.00') {
                $discount_value = 0;
                $total = $unit_price * $qty;;
            }
            else {
                $total = $unit_price * $qty - $discount_value;
            }

            $obj  = new stdClass();
            $obj->codice = $codice;
            $obj->descrizione = $desc;
            $obj->qty = $qty;
            $obj->unit_price = $unit_price;
            //$obj->unit_discount_price = $discount_value/$qty;
            $obj->unit_discount_price = $unit_price - ($discount_value/$qty);
            $obj->discount_value = $discount_value;
            $obj->total = $total;                             //RINO 07/09/2016
            //$obj->total = $total +  $line['tax_amount'];        //RINO 07/09/2016
            $items[] = $obj;

        }

        //mette gli sconti
        $lista_sconti = $this->getInfoPromotionNew($increment_id);
        foreach ($lista_sconti as $sconto) {
            $obj  = new stdClass();
            $obj->codice = $sconto->promotion_id;
            $obj->descrizione = $sconto->campaign_id;
            $obj->qty = 1;
            $obj->unit_price = $sconto->valore * -1;
            $obj->unit_discount_price = $sconto->valore * -1;
            $obj->discount_value = 0;
            $obj->total = $sconto->valore * -1;
            $items[] = $obj;

        }

        return $items;
    }

    private function getOrderLines($increment_id, $country) {  //RINO 09/09/2016

        //$infoFiscale = $this->getInfoFiscale($increment_id);    //RINO 23/09/2016
        $items = array();
        $total_discount=0;
        $con = OMDBManager::getMagentoConnection();
            $sql ="SELECT d.*, m.vs_flag from sales_flat_order_item d, sales_flat_order m WHERE d.order_id=m.entity_id AND m.increment_id='$increment_id'";
        //echo "\nSQL: ".$sql;
            $res = mysql_query($sql);
            $qty_ordered = 0;
            $totale_sconto_righe = 0;
            while ($row=mysql_fetch_object($res)) {

                $obj  = new stdClass();
                if ($row->vs_flag) {
                    $obj->codice = $row->sku;
                    $obj->descrizione = $row->name;
                    $obj->qty = $row->qty_ordered;
                    $obj->total = $row->row_total - $row->discount_amount; //questo perhè original_discount è negativo
                    $totale_sconto_righe += $row->discount_amount;
                    $obj->discount_value = $row->discount_amount;

                    $obj->unit_discount_price = $obj->total/$row->qty_ordered;
                    $obj->unit_price = $row->row_total/$row->qty_ordered;

                } else {
                    $obj->codice = $row->sku;
                    $obj->descrizione = $row->name;
                    $obj->qty = $row->qty_ordered;
                    $obj->total = $row->row_total + $row->original_discount; //questo perhè original_discount è negativo
                    $obj->discount_value = $row->original_discount;
                    $totale_sconto_righe += ( $row->original_discount * -1);

                    $obj->unit_discount_price = $obj->total/$row->qty_ordered;
                    $obj->unit_price = $row->row_total/$row->qty_ordered;
                }
                $items[] = $obj;

                //print_r($obj);

            }

        //14112016 - Aggiungere riga con sconti di riga complessivi
        if ($totale_sconto_righe>0) {
            $obj = new stdClass();
            $obj->codice="";
            if (strtoupper($country)=='IT')
                $obj->descrizione="Totale sconti riga applicati: € ".number_format(floatval($totale_sconto_righe),2);
            elseif (strtoupper($country)=='ES')
                $obj->descrizione="Rebajas totales en artículos: € ".number_format(floatval($totale_sconto_righe),2);
            else
                $obj->descrizione="Total rebates on items: € ".number_format(floatval($totale_sconto_righe),2);

            $obj->qty = '';
            $obj->unit_price = '';
            $obj->unit_discount_price = '';
            $obj->discount_value = '';
            $obj->total = '';
            echo "\nSconto riga totale: ".$totale_sconto_righe;

            $items[] = $obj;
        }

        //mette gli sconti
        $lista_sconti = $this->getInfoPromotionNew($increment_id);
        foreach ($lista_sconti as $sconto) {

            $obj  = new stdClass();
            $obj->codice = $sconto->promotion_id;
            $obj->descrizione = $sconto->campaign_id;
            $obj->qty = 1;
            $obj->unit_price = $sconto->valore * -1;
            $obj->unit_discount_price = $sconto->valore * -1;
            $obj->discount_value = 0;
            $obj->total = $sconto->valore * -1;
            $items[] = $obj;

        }


        return $items;
    }

    private function getShippingCharge($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $promoObjArray = $orderDBHelper->getShippingPromotion();
        $shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);          // RINO 30/07/2016
        //$shippingAmount = number_format($order->getBaseShippingInclTax() + ($order->getBaseShippingDiscountAmount() * -1),2);       // RINO 30/07/2016
        $shippingDiscount = 0;
        foreach ($promoObjArray as $promoObj) {
            $shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);      // RINO 30/07/2016
            //$shippingAmount = number_format($order->getBaseShippingInclTax() + ($order->getBaseShippingDiscountAmount() * -1),2);   // RINO 30/07/2016
            $shippingDiscount = number_format(($promoObj->value * -1),2);
        }

        //echo "\nShipping Amount: ".$shippingAmount;
        //echo "\nShipping Discount: ".$shippingDiscount;
        $obj = new stdClass();
        $obj->shippingAmount = $shippingAmount;
        $obj->shippingDiscount = $shippingDiscount;



        if ($shippingDiscount=='0.00') {
            //echo "\nNessuno sconto";
            $obj->total = $obj->shippingAmount;
            $obj->shippingValoreScontato = $obj->shippingAmount;
        } else {
            $obj->total =  $shippingAmount - $obj->shippingDiscount;
            $obj->shippingValoreScontato = $shippingAmount -$obj->shippingDiscount;
            $obj->shippingAmount = $shippingAmount - $obj->shippingDiscount;
        }

        return $obj;
    }

    private function getPayment($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $payment = $order->getPayment();
        $payment_method_selected = $payment->getMethod();
        $obj = new stdClass();
        $obj->payment_method = $payment_method_selected;
        if ($payment_method_selected=='ccsave') {
            $obj->description_line1 ="SGIT - Credit Card";
            $obj->description_line2 =$payment->getCcType()."|".$payment->getCcOwner()."|".$payment->getCcLast4();
        } elseif ($payment_method_selected=='cashondelivery') {
            $obj->description_line1 ="SGIT - Contanti";
            $obj->description_line2 = "";
        } elseif ($payment_method_selected=='free') {  // RINO 27/07/2016  Gestione chiosco
            $obj->description_line1 ="SGIT - Chiosco";
            $obj->description_line2 = "";
        } else {
            //PayPal
            $obj->description_line1 ="SGIT - Paypal";
            $obj->description_line2 = "";
        }


        return $obj;

    }

    private function process($lista_ordini) {
        $lista_record = array();
        $start_date = date('d/m/Y H:i:s');

        foreach ($lista_ordini as $item) {
            //echo "\nITem: ";
            //print_r($item);
            $increment_id =$item->increment_id;
            //echo "\nIncrementID Obj: ".$increment_id;
            $dw_order_number =$item->dw_order_number;
            $delivery_id = $item->delivery_id;
            $flag_tipo_spedizione = $item->flag_tipo_spedizione;

            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
            $order_customer_locale=$order->getCustomerLocale();
            $ocl=substr($order_customer_locale,0,2);

           //  echo "\nGetInfoFiscale";
            $infoFiscale = $this->getInfoFiscale($increment_id, $ocl);

            if ($flag_tipo_spedizione==2 || $flag_tipo_spedizione==3){//significa ordine completo
                if ($order->getData('needInvoice')=='true' || $infoFiscale->sopra_soglia=='1')
                    $this->generaFattura($increment_id, $order_customer_locale, $flag_tipo_spedizione);
                else
                    $this->generaScontrino($increment_id, $order_customer_locale, $flag_tipo_spedizione);
            }
           // echo "\nInvio Email";
            $this->inviaEmailConfermaOrdine($increment_id, $order_customer_locale , $dw_order_number, $delivery_id, $flag_tipo_spedizione);
            $this->removeConfermaOrdine($increment_id);
            sleep(1);
        }
    }

    private function generaScontrino($increment_id, $order_customer_locale, $flag_tipo_spedizione) {

        if ($order_customer_locale=="default") $order_customer_locale="italia";
        $ocl=substr($order_customer_locale,0,2);

        $host = $this->config->getHost();

        /*$template 		= "/home/OrderManagement/email/template/template_scontrino.php";
        if (strtolower($ocl)=='en')
            $template 		= "/home/OrderManagement/email/template/template_scontrino_en.php";
        */
        $template 		= "/home/OrderManagement/email/template/template_scontrino_".strtolower($ocl).".php";  //RINO 07/10/2016

        //info cliente
        $info_cliente = $this->getInfoCliente($increment_id);


        //info ordine
        $info_ordine = $this->getInfoOrdine($increment_id, $ocl);

        // print_r($info_ordine);
        if ($info_ordine->click_collect) {
            $template 		= "/home/OrderManagement/email/template/template_scontrino_cc.php";
        }
        $infoFiscale = $this->getInfoFiscale($increment_id, $ocl);  // RINO 04/09/2016
        $invoicepath = "/tmp/Ricevuta_OV_".$infoFiscale->codice_ente."0_".$info_ordine->scontrino.".pdf";  // RINO 04/09/2016 $invoicepath = "/tmp/Ricevuta_OV_".$info_ordine->scontrino.".pdf";

        //info destinatario
        $info_destinatario = $this->getInfoDestinatario($increment_id);


        //righe ordine
        $items = $this->getOrderLines($increment_id,$infoFiscale->country);  //RINO 07/09/2016

        //payment
        $payment = $this->getPayment($increment_id);

        //shipping
        $shipping_charge = $this->getShippingCharge($increment_id);

        //echo "\nGeneazione pdf";
        ob_start();
        include($template);

        $html = ob_get_contents();
        ob_end_clean();

        $html = trim($html);

        $dompdf = new DOMPDF();
        $dompdf->set_paper("A4");

        // Carica template fattura
        $dompdf->load_html($html);

        $dompdf->render();
        $canvas = $dompdf->get_canvas();

        //$canvas->page_text(30, 810, "Pagina: {PAGE_NUM} di {PAGE_COUNT}", '', 8, array(0, 0, 0));

        // Salva PDF su filesystem
        //unlink($invoicepath);
        $response= file_put_contents($invoicepath, $dompdf->output());

        return $response;

    }

    private function getInfoFiscale($increment_id, $customer_locale )
    {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');

        $billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());
        $billing_country = $billingAddress->getData('country_id');

        $country_details = CountryDBHelper::getCountryDetails($billing_country);
        $country = $country_details->sopra_soglia == '1' ? $billing_country : 'it';
        $rapprFiscale = $country_details->sopra_soglia == '1' ? $country_details->rappr_fiscale : '';


        $infoFiscale  = new stdClass();
        $infoFiscale->country = $country;
        $infoFiscale->rapprFiscale = $rapprFiscale;
        $infoFiscale->sopra_soglia = $country_details->sopra_soglia;
        $infoFiscale->codice_ente = $country_details->codice_ente;    //RINO 04/09/2016
        $infoFiscale->iva = $country_details->iva;                    //RINO 23/09/2016
        $infoFiscale->registro_iva = $country_details->registro_iva;                    //RINO 23/09/2016



        if ($customer_locale) {
            $header_name = "header_".$customer_locale;
            //echo "\nHeader name: ".$header_name;

            $infoFiscale->header = $country_details->$header_name;


            $footer_name = "footer_".$customer_locale;
            //echo "\nFooter name: ".$footer_name;
            $infoFiscale->footer = $country_details->$footer_name;
        }



        return $infoFiscale;
    }

    private function generaFattura($increment_id, $order_customer_locale) {

        if ($order_customer_locale=="default") $order_customer_locale="italia";
        $ocl=substr($order_customer_locale,0,2);
        //$ocl = "es";
        echo "\nCustomer Locale: ".$order_customer_locale;


        $host = $this->config->getHost();

        $infoFiscale = $this->getInfoFiscale($increment_id, $ocl);




        //$template 		= "/home/OrderManagement/email/template/template_fattura_".strtolower($infoFiscale->country).".php";

        $template 		= "/home/OrderManagement/email/template/template_fattura_".strtolower($ocl).".php";  //RINO 07/10/2016
        //echo "\nTemplate: ".$template;

        //info cliente
        $info_cliente = $this->getInfoCliente($increment_id);


        //info ordine
        $info_ordine = $this->getInfoOrdine($increment_id, null); //informazione su delivery non mi serve

        if ($info_ordine->click_collect)
            $template 		= "/home/OrderManagement/email/template/template_fattura_".$infoFiscale->country."_cc.php";

        $invoicepath = "/tmp/FATTURA_OV_".$infoFiscale->codice_ente."0_".$info_ordine->num_fattura.".pdf";  // RINO 04/09/2016 $invoicepath = "/tmp/FATTURA_OV_".$info_ordine->num_fattura.".pdf";

        //info destinatario
        $info_destinatario = $this->getInfoDestinatario($increment_id);


        //righe ordine
        $items = $this->getOrderLines($increment_id, $infoFiscale->country);  //RINO 07/09/2016

        //payment
        $payment = $this->getPayment($increment_id);

        //shipping
        $shipping_charge = $this->getShippingCharge($increment_id);

        echo "\nGeneazione pdf";
        ob_start();
        include($template);
        $html = ob_get_contents();
        ob_end_clean();

        $html = trim($html);

        $dompdf = new DOMPDF();
        $dompdf->set_paper("A4");

        // Carica template fattura
        $dompdf->load_html($html);
        $dompdf->render();
        $canvas = $dompdf->get_canvas();

        //$canvas->page_text(30, 810, "Pagina: {PAGE_NUM} di {PAGE_COUNT}", '', 8, array(0, 0, 0));

        // Salva PDF su filesystem
        //unlink($invoicepath);
        $response= file_put_contents($invoicepath, $dompdf->output());
        //echo "\nGeneratra fattura: ".$invoicepath;
        return $response;
    }

    private function inviaEmailConfermaOrdine($increment_id, $order_customer_locale, $dw_order_number, $delivery_id, $flag_tipo_spedizione) {

        if ($order_customer_locale=="default") $order_customer_locale="italia";
        echo "\nIncrement_id conferma : ".$increment_id." delivery_id: ".$delivery_id;
        $ocl=substr($order_customer_locale,0,2);
        if ($flag_tipo_spedizione==1)
            $template_email 		= "/home/OrderManagement/email/template/template_email_conferma_ordine_parziale_".$ocl.".php"; //significa ordine parziale
        else
            $template_email 		= "/home/OrderManagement/email/template/template_email_conferma_ordine_".$ocl.".php";

        //info cliente
        $info_cliente = $this->getInfoCliente($increment_id);
        //print_r($info_cliente->getData());

        //info ordine
        $info_ordine = $this->getInfoOrdine($increment_id, $delivery_id);
        print_r($info_ordine);


        if ($info_ordine->click_collect) {
            if ($flag_tipo_spedizione==1)
                $template_email = "/home/OrderManagement/email/template/template_email_conferma_ordine_parziale_".$ocl."_cc.php";
            else
                $template_email = "/home/OrderManagement/email/template/template_email_conferma_ordine_".$ocl."_cc.php";
        }


        $infoFiscale = $this->getInfoFiscale($increment_id, $ocl);

        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $invoicepath=null;
        if ($flag_tipo_spedizione==2) {//allegato solo se ordine completo
            if ($order->getData('needInvoice')=='true' || $infoFiscale->sopra_soglia=='1')
                $invoicepath = "/tmp/FATTURA_OV_".$infoFiscale->codice_ente."0_".$info_ordine->num_fattura.".pdf";
            else
                $invoicepath = "/tmp/Ricevuta_OV_".$infoFiscale->codice_ente."0_".$info_ordine->scontrino.".pdf";
        }

        //echo "\nPrepara Template Email";
        ob_start();
        include($template_email);
        $message = ob_get_contents();
        ob_end_clean();


        $message = trim($message);
        $email_address = $info_ordine->email;

        $mail = new PHPMailer;
        $mail->CharSet = "UTF-8";
        $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
        $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
        $mail->From 	= 'noreply@ovs.it';
        $mail->FromName = 'OVS Online Store';
        switch($ocl) {  // Rino 07/08/2016 oggetto mail per lingua d'ordine
            case 'it': $mail->Subject = $infoFiscale->codice_ente. ' - Conferma Spedizione OVS #'.$info_ordine->ordine; break;
            case 'en': $mail->Subject = $infoFiscale->codice_ente. ' - Shipping Confirmation OVS #'.$info_ordine->ordine; break;
            case 'es': $mail->Subject = $infoFiscale->codice_ente. ' - Confirmación de envío OVS #'.$info_ordine->ordine; break;
        }

        $mail->Body		= $message;

       // $email_address="vincenzo.sambucaro@h-farm.com";

        $mail->addAddress($email_address);

        $mail->addBCC('nomovs@gmail.com');
        $mail->addBCC('ecommerce.tracking@ovs.it');
        $mail->addBCC('ovs@evologi.it');
        $mail->addBCC('ovs.confirmorder@everis.com');
        $mail->addBCC('support.ovs@nuvo.it');

        if ($info_ordine->click_collect && $info_ordine->email_negozio)
            $mail->addBCC($info_ordine->email_negozio);

        if ($info_ordine->need_invoice=='true' || $infoFiscale->sopra_soglia=='1' ) {

            $mail->addBCC('amministrazione.eshop@ovs.it');

            if ($flag_tipo_spedizione==2) {
                $mail->addBCC('gessica.rizzi@ovs.it');
                $mail->addBCC('luca.perini@ovs.it');
            }

            if ($infoFiscale->sopra_soglia=='1') {
                $mail->addBCC('j.galvan@diligens.es');
            }

        }

        echo "\n".$mail->AddEmbeddedImage("/home/OrderManagement/email/template/logo.jpg", "logo_ovs");
        if ($flag_tipo_spedizione==2 && $invoicepath) {//allegato solo se ordine completo
           // echo "\nAllegato: ".$invoicepath;
            $mail->addAttachment($invoicepath);
        }
        $mail->isHTML(true);
        $mail->send();

        if ($flag_tipo_spedizione==2 )
            $this->log->LogDebug("Inviata conferma ordine: ".$email_address." per ordine: ".$info_ordine->ordine);
        else
            $this->log->LogDebug("Inviata conferma ordine parziale: ".$email_address." per ordine: ".$info_ordine->ordine.", delivery_id:".$delivery_id);

        // print_r($message);


    }

    private function getListaConfermaOrdine() {

        $con = OMDBManager::getConnection();

        $sql ="SELECT * FROM conferma_ordine limit 50";
        //echo "\nLog: ".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $obj = new stdClass();
            $obj->increment_id = $row->increment_id;
            $obj->dw_order_number = $row->dw_order_no;
            $obj->delivery_id = $row->delivery_id;
            $obj->flag_tipo_spedizione = $row->flag_tipo_spedizione;
            $lista[] = $obj;
        }

        OMDBManager::closeConnection($con);

        $this->log->LogDebug("Record trovati:".sizeof($lista));
        print_r($lista);
        return $lista;
    }

    private function removeConfermaOrdine($increment_id) {


        $con = OMDBManager::getConnection();

        $sql ="DELETE FROM conferma_ordine WHERE increment_id='$increment_id'";
        //echo "\nLog: ".$sql;
        $res = mysql_query($sql);

        OMDBManager::closeConnection($con);

    }

    public function getListaIncrementIdByEntityId($entity_id) {
        $con = OMDBManager::getMagentoConnection();

        $sql = "SELECT increment_id FROM sales_flat_order WHERE entity_id='$entity_id'";
        echo "\nSQL: ".$sql;
        $res = mysql_query($sql);
        $result = null;
        while ($row = mysql_fetch_object($res)) {
            $result = $row->increment_id;
        }
        OMDBManager::closeConnection($con);
        return $result;
    }

}

$t = new ConfirmOrder();
$lista= null;

$t->export();
