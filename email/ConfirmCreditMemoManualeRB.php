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
require_once realpath(dirname(__FILE__))."/../creditmemo/CreditMemoHelper.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();

class ConfirmCreditMemo {


    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/confirm_creditmemo.log',KLogger::DEBUG);

    }

    /**
     * Inizia export flusso invio ordini
     */
    public function export($lista=null) {

        $this->log->LogInfo("Start");
        if ($lista!=null)
            $lista_creditmemo = $lista;
        else
            $lista_creditmemo = $this->getListaCreditMemo();


        if ($lista_creditmemo)
            $this->process($lista_creditmemo);
        else
            $this->log->LogInfo("\nNessun CreditMemo da esportare");


    }

    private function getInfoCliente($order_id) {
        $order = Mage::getModel('sales/order')->load($order_id);
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();

        return $order->getBillingAddress();

    }

    private function getCountryIva($order) {     //RINO 13/09/2016

        $billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());
        $billing_country = $billingAddress->getData('country_id');
        $country_details = CountryDBHelper::getCountryDetails($billing_country);
        $country_iva= $country_details->iva;
        return $country_iva;
    }

    private function getInfoCreditMemo($creditmemo_id,$order_id) {  //RINO 05/09/2016
        $obj = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);
        $infoFiscale = $this->getInfoFiscale($order_id); //RINO 05/09/2016
        $infoCreditMemo = new stdClass();
        $infoCreditMemo->amount = number_format($obj->grand_total,2);
        //echo "\nAmount: ".$infoOrdine->amount;

        /*if (strtolower($infoFiscale->country)=='es')
            $imponibile_tmp = $obj->grand_total/1.21;
        else
            $imponibile_tmp = $obj->grand_total/1.22; // RINO 05/09/2016 $imponibile_tmp = $obj->grand_total/1.22;
        */
        $imponibile_tmp = $obj->grand_total / $infoFiscale->country_iva;  // RINO 23/09/2016
        //echo "\nImponibile tmp: ".$imponibile_tmp;
        $infoCreditMemo->imponibile = number_format(round($imponibile_tmp,2),2);
        //echo "\nImponibile: ".$infoOrdine->imponibile;
        $infoCreditMemo->iva = $infoCreditMemo->amount - $infoCreditMemo->imponibile;

        $infoCreditMemo->scontrino = $obj->bill_number;
        $infoCreditMemo->data_documento = $obj->bill_date;

        $infoCreditMemo->num_fattura = $obj->invoice_number;
        $infoCreditMemo->data_documento_fattura = $obj->invoice_date;
        $infoCreditMemo->shipping_amount = $obj->shipping_amount;

        // print_r($infoCreditMemo);
        return $infoCreditMemo;


    }

    private function getInfoOrdine($order_id) {
        $order = Mage::getModel('sales/order')->load($order_id);
        // print_r($order->getData());
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $order_custom_attributes = $orderDBHelper->getCustomAttributes();

        $piva = $order_custom_attributes['partitaIva'] ? "IT".$order_custom_attributes['partitaIva']: "";
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
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $billing_address= $order->getBillingAddress();

        $infoOrdine = new stdClass();
        $infoOrdine->cliente = $codice_cliente_dw;
        $infoOrdine->scontrino = $scontrino;
        $infoOrdine->data_documento = $data_documento;
        $infoOrdine->ordine = $order_no;
        $infoOrdine->data_ordine = $newDate_ordine;
        $infoOrdine->telefono = $billing_address->getTelephone();
        $infoOrdine->email = $customer->getEmail();
        $infoOrdine->num_fattura = $fattura;
        $infoOrdine->data_documento_fattura = $data_documento_fattura;
        $infoOrdine->need_invoice = $order->getData('needInvoice');
        $infoOrdine->rag_sociale_nome = $rag_sociale_nome;
        $infoOrdine->rag_sociale_cognome = $rag_sociale_cognome;
        $infoOrdine->piva = $piva;
        $infoOrdine->cf = $cf;


        $infoOrdine->amount = number_format($order->getBaseGrandTotal(),2);
        //echo "\nAmount: ".$infoOrdine->amount;
        $imponibile_tmp = $order->getBaseGrandTotal()/1.22;
        //echo "\nImponibile tmp: ".$imponibile_tmp;
        $infoOrdine->imponibile = number_format(round($imponibile_tmp,2),2);
        //echo "\nImponibile: ".$infoOrdine->imponibile;
        $infoOrdine->iva = $infoOrdine->amount - $infoOrdine->imponibile;

        return $infoOrdine;

    }

    private function getInfoDestinatario($order_id) {
        $order = Mage::getModel('sales/order')->load($order_id);
        $shipping_address= $order->getShippingAddress();
        return $shipping_address;
    }

    private function getOrderLines($increment_id) {

        $items = array();
        $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
        //print_r($lines);
        foreach ($lines as $line) {
            $codice = $line['sku'];
            $desc = $line['description'];
            $qty = $line['order_quantity'];
            $unit_price =  $line['unit_price'];
            $discount_value = $line['discount_value'];
            if ($discount_value=='0.00') $discount_value = $unit_price * $qty;
            $total = $discount_value;

            $obj  = new stdClass();
            $obj->codice = $codice;
            $obj->descrizione = $desc;
            $obj->qty = $qty;
            $obj->unit_price = $unit_price;
            $obj->unit_discount_price = $discount_value/$qty;
            $obj->discount_value = $discount_value;
            $obj->total = $total;
            $items[] = $obj;

        }

        return $items;
    }

    private function getCreditMemoLines($creditmemo_id) {

        $items = array();
        $lines = CreditMemoHelper::getCreditMemoItems($creditmemo_id);
        //print_r($lines);
        foreach ($lines as $lineItem) {

            $obj  = new stdClass();
            $obj->codice = $lineItem->sku;
            $obj->descrizione = $lineItem->name;
            $obj->qty = -1 * $lineItem->qty;
            $obj->unit_price = $lineItem->base_price;
            //$obj->unit_discount_price = $lineItem->base_price                             //RINO 07/09/2016
            $obj->unit_discount_price = $lineItem->base_price - $lineItem->discount_value;  //RINO 07/09/2016
            $obj->discount_value = $lineItem->base_price;
            //$obj->total = -1 * $lineItem->row_total;                                      //RINO 07/09/2016
            $obj->total = $obj->qty * ($obj->unit_discount_price + $lineItem->tax_amount);  //RINO 07/09/2016
            $items[] = $obj;

        }

        return $items;
    }

    private function getShippingChargeOLD($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $orderDBHelper = new OrderDBHelper($order->getDwOrderNumber());
        $promoObjArray = $orderDBHelper->getShippingPromotion();
        $shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);
        $shippingDiscount = 0;
        foreach ($promoObjArray as $promoObj) {
            $shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);
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

    private function getShippingCharge($info_creditmemo) {
        $obj = new stdClass();
        $obj->total =  $info_creditmemo->shipping_amount * -1 ;
        $obj->shippingValoreScontato = $info_creditmemo->shipping_amount * -1;
        $obj->shippingAmount = $info_creditmemo->shipping_amount * -1;

        return $obj;
    }

    private function getPayment($order_id) {
        $order = Mage::getModel('sales/order')->load($order_id);
        $payment = $order->getPayment();
        $payment_method_selected = $payment->getMethod();
        $obj = new stdClass();
        $obj->payment_method = $payment_method_selected;
        if ($payment_method_selected=='ccsave') {
            $obj->description_line1 ="PAGAMENTO: SGIT - Credit Card";
            $obj->description_line2 =$payment->getCcType()."|".$payment->getCcOwner()."|".$payment->getCcLast4();
        } elseif ($payment_method_selected=='cashondelivery') {
            $obj->description_line1 ="PAGAMENTO: SGIT - Contanti";
            $obj->description_line2 = "";
        } elseif ($payment_method_selected=='free') {  // RINO 27/07/2016  Gestione chiosco
            $obj->description_line1 ="PAGAMENTO: SGIT - Chiosco";
            $obj->description_line2 = "";
        } else {
            //PayPal
            $obj->description_line1 ="PAGAMENTO: SGIT - Paypal";
            $obj->description_line2 = "";
        }


        return $obj;

    }

    private function process($lista_creditmemo) {
        $lista_record = array();
        $start_date = date('d/m/Y H:i:s');


        foreach ($lista_creditmemo as $obj) {
            $order_id = $obj->order_id;
            $creditmemo_id = $obj->creditmemo_id;
            $infoFiscale = $this->getInfoFiscale($order_id);
            $order = Mage::getModel('sales/order')->load($order_id);
            if ($order->getData('needInvoice')=='true' || $infoFiscale->sopra_soglia=='1')
                $response = $this->generaFattura($order_id, $creditmemo_id);
            else
                $response = $this->generaScontrino($order_id, $creditmemo_id);


            $this->inviaEmailConfermaOrdine($order_id, $creditmemo_id, $order->getCustomerLocale()); // Rino 15/07/ aggiunto multilingua
            $this->removeConfermaCreditMemo($creditmemo_id);
        }
    }

    private function generaScontrino($order_id, $creditmemo_id) {

        $host = $this->config->getHost();

        $template 		= "/home/OrderManagement/email/template/template_creditmemo_scontrino.php";


        //info cliente
        $info_cliente = $this->getInfoCliente($order_id);


        //info ordine
        $info_ordine = $this->getInfoOrdine($order_id);

        $info_creditmemo = $this->getInfoCreditMemo($creditmemo_id,$order_id); // RINO 05/09/2016

        $invoicepath = "/tmp/Ricevuta_OV_".$info_creditmemo->scontrino.".pdf";


        //info destinatario
        $info_destinatario = $this->getInfoDestinatario($order_id);

        //righe ordine
        //$items = $this->getOrderLines($increment_id);
        $items = $this->getCreditMemoLines($creditmemo_id);

        //payment
        $payment = $this->getPayment($order_id);

        //shipping
        $shipping_charge = $this->getShippingCharge($order_id);

        //info creditmemo
        $info_creditmemo = $this->getInfoCreditMemo($creditmemo_id,$order_id); // RINO 05/09/2016

        //echo "\nGeneazione pdf creditmemo";
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
        $response= file_put_contents($invoicepath, $dompdf->output());

        return $response;

    }

    private function generaFattura($order_id, $creditmemo_id) {

        $host = $this->config->getHost();

        $infoFiscale = $this->getInfoFiscale($order_id);

        $template 		= "/home/OrderManagement/email/template/template_creditmemo_fattura_".strtolower($infoFiscale->country).".php";

        //info cliente
        $info_cliente = $this->getInfoCliente($order_id);


        //info ordine
        $info_ordine = $this->getInfoOrdine($order_id);

        $info_creditmemo = $this->getInfoCreditMemo($creditmemo_id,$order_id); // RINO 05/09/2016


        $invoicepath = "/tmp/FATTURA_OV_".$info_creditmemo->num_fattura.".pdf";


        //info destinatario
        $info_destinatario = $this->getInfoDestinatario($order_id);


        //righe ordine
        //$items = $this->getOrderLines($increment_id);
        $items = $this->getCreditMemoLines($creditmemo_id);

        //payment
        $payment = $this->getPayment($order_id);

        //shipping
        $shipping_charge = $this->getShippingCharge($info_creditmemo);

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
        $response= file_put_contents($invoicepath, $dompdf->output());

        return $response;
    }

    private function inviaEmailConfermaOrdine($order_id, $creditmemo_id, $order_customer_locale) {

        $ocl=substr($order_customer_locale,0,2);
        $template_email 		= "/home/OrderManagement/email/template/template_email_conferma_creditmemo_".$ocl.".php";

        //info cliente
        $info_cliente = $this->getInfoCliente($order_id);
        //print_r($info_cliente->getData());

        //info ordine
        $info_ordine = $this->getInfoOrdine($order_id);

        //info CrediMemo lines
        $items = $this->getCreditMemoLines($creditmemo_id);

        //info creditmemo
        $info_creditmemo = $this->getInfoCreditMemo($creditmemo_id,$order_id); // RINO 05/09/2016

        $infoFiscale = $this->getInfoFiscale($order_id);

        if ($info_ordine->need_invoice=='true' || $infoFiscale->sopra_soglia=='1')
            $invoicepath = "/tmp/FATTURA_OV_".$info_creditmemo->num_fattura.".pdf";
        else
            $invoicepath = "/tmp/Ricevuta_OV_".$info_creditmemo->scontrino.".pdf";

        print_r($invoicepath);
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
            case 'it': $mail->Subject = 'Conferma Rimborso OVS #'.$info_ordine->ordine; break;
            case 'en': $mail->Subject = 'Return Exchange Confirmation OVS #'.$info_ordine->ordine; break;
            case 'es': $mail->Subject = 'Confirmación de Reembolso OVS #'.$info_ordine->ordine; break;
        }
        $mail->Body		= $message;

        /*if ($email_address!=null)
            $mail->addAddress($email_address);  // RINO 01/09/2016
        else
            $mail->addAddress('ovs.confirmorder@everis.com');
        */
        $mail->addAddress('nomovs@gmail.com');   // todo togliere dopo test

        //$mail->addBCC('ecommerce.tracking@ovs.it');
        //$mail->addBCC('ovs@evologi.it');
        //$mail->addBCC('nomovs@gmail.com');
        //$mail->addBCC('ovs.confirmorder@everis.com');
        //$mail->addBCC('support.ovs@nuvo.it');


        if ($info_ordine->need_invoice=='true' || $infoFiscale->sopra_soglia=='1') {

            //$mail->addBCC('amministrazione.eshop@ovs.it');
            //$mail->addBCC('gessica.rizzi@ovs.it');
            //$mail->addBCC('luca.perini@ovs.it');

            if (strtolower($infoFiscale->country) == 'es') {
                //$mail->addBCC('j.galvan@diligens.es');
            }
        }
        $mail->AddEmbeddedImage("template/logo.jpg", "logo_ovs", "template/logo.jpg", "base64", "image/png");
        $mail->addAttachment($invoicepath);
        $mail->isHTML(true);

        if(!$mail->send())
        {
            echo "Mailer Error: " . $mail->ErrorInfo;
        }
        // print_r($message);


    }

    private function getInfoFiscale($order_id)
    {
        $order = Mage::getModel('sales/order')->load($order_id, 'entity_id');

        $billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());
        $billing_country = $billingAddress->getData('country_id');

        $country_details = CountryDBHelper::getCountryDetails($billing_country);
        $country = $country_details->sopra_soglia == '1' ? $billing_country : 'it';
        $rapprFiscale = $country_details->sopra_soglia == '1' ? $country_details->rappr_fiscale : '';


        $infoFiscale  = new stdClass();
        $infoFiscale->country = $country;
        $infoFiscale->rapprFiscale = $rapprFiscale;
        $infoFiscale->sopra_soglia = $country_details->sopra_soglia;
        $infoFiscale->country_iva = $country_details->iva; // RINO 23/09/2016

        return $infoFiscale;
    }

    private function getListaCreditMemo() {

        $con = OMDBManager::getConnection();

        $sql ="SELECT * FROM conferma_creditmemo";
        //echo "\nLog: ".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $obj = new stdClass();
            $obj->creditmemo_id = $row->creditmemo_id;
            $obj->order_id = $row->order_id;
            $lista[] = $obj;
        }

        OMDBManager::closeConnection($con);

        $this->log->LogDebug("Record trovati:".sizeof($lista));
        print_r($lista);

        return $lista;
    }

    private function removeConfermaCreditMemo($creditmemo_id) {

        $con = OMDBManager::getConnection();

        $sql ="DELETE FROM conferma_creditmemo WHERE creditmemo_id='$creditmemo_id'";
        //echo "\nLog: ".$sql;
        $res = mysql_query($sql);

        OMDBManager::closeConnection($con);

    }

}

//TODO METTERE LA DATA AUTOMATICA
$t = new ConfirmCreditMemo();

//MANUALE
$obj = new stdClass();
$obj->creditmemo_id = "418";
$obj->order_id =  "64703";
$lista_creditmemo[] = $obj;
$t->export($lista_creditmemo);

//AUTOMATICO
//$t->export();
