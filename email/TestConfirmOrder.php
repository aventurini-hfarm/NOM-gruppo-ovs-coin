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

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();

class TestConfirmOrder {

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
    public function export($array_lista_ordini) {

        $this->log->LogInfo("Start");
        $lista_ordini = $array_lista_ordini;

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

    private function getInfoOrdine($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
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

    private function getInfoDestinatario($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $shipping_address= $order->getShippingAddress();
        return $shipping_address;
    }

    private function getInfoPromotion($increment_id) {
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

        //mette gli sconti
        $lista_sconti = $this->getInfoPromotion($increment_id);
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
        $promoObj = $orderDBHelper->getShippingPromotion();
        $shippingAmount = number_format($order->getShippingAmount() + ($order->getBaseShippingDiscountAmount() * -1),2);
        $shippingDiscount = number_format(($promoObj->value * -1),2);


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
        echo "\nShipping: ";
        print_r($obj);
        return $obj;
    }

    private function getPayment($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $payment = $order->getPayment();
        $payment_method_selected = $payment->getMethod();
        $obj = new stdClass();
        $obj->payment_method = $payment_method_selected;
        if ($payment_method_selected=='ccsave') {
            $obj->description_line1 ="PAGAMENTO: SGIT - Credit Card";
            $obj->description_line2 =$payment->getCcType()."|".$payment->getCcOwner()."|".$payment->getCcLast4();
        } else {
            //PayPal
            $obj->description_line1 ="PAGAMENTO: SGIT - Paypal";
            $obj->description_line2 = "";
        }


        return $obj;

    }

    private function process($lista_ordini) {
        $lista_record = array();
        $start_date = date('d/m/Y H:i:s');

        foreach ($lista_ordini as $increment_id) {
            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
            if ($order->getData('needInvoice')!='true')
                $response = $this->generaScontrino($increment_id);
            else
                $response = $this->generaFattura($increment_id);

            //$this->inviaEmailConfermaOrdine($increment_id, $response);

        }
    }

    private function generaScontrino($increment_id) {
        $template 		= "/home/OrderManagement/email/template/template_scontrino.php";


        //info cliente
        $info_cliente = $this->getInfoCliente($increment_id);


        //info ordine
        $info_ordine = $this->getInfoOrdine($increment_id);

        $invoicepath = "/tmp/TEST_Ricevuta_OV_".$info_ordine->scontrino.".pdf";

        //info destinatario
        $info_destinatario = $this->getInfoDestinatario($increment_id);


        //righe ordine
        $items = $this->getOrderLines($increment_id);

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
        $response= file_put_contents($invoicepath, $dompdf->output());

        return $response;

    }

    private function generaFattura($increment_id) {
        $template 		= "/home/OrderManagement/email/template/template_fattura.php";


        //info cliente
        $info_cliente = $this->getInfoCliente($increment_id);


        //info ordine
        $info_ordine = $this->getInfoOrdine($increment_id);

        $invoicepath = "/tmp/TEST_FATTURA_CC_".$info_ordine->num_fattura.".pdf";

        //info destinatario
        $info_destinatario = $this->getInfoDestinatario($increment_id);


        //righe ordine
        $items = $this->getOrderLines($increment_id);

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
        $response= file_put_contents($invoicepath, $dompdf->output());

        return $response;
    }

    private function inviaEmailConfermaOrdine($increment_id, $response) {

        //return;

        $template_email 		= "/home/OrderManagement/email/template/template_email_conferma_ordine.php";

        //info cliente
        $info_cliente = $this->getInfoCliente($increment_id);
        //print_r($info_cliente->getData());

        //info ordine
        $info_ordine = $this->getInfoOrdine($increment_id);
        if ($info_ordine->need_invoice!='true')
            $invoicepath = "/tmp/TEST_Ricevuta_OV_".$info_ordine->scontrino.".pdf";
        else
            $invoicepath = "/tmp/TEST_FATTURA_CC_".$info_ordine->num_fattura.".pdf";
        //echo "\nPrepara Template Email";
        ob_start();
        include($template_email);
        $message = ob_get_contents();
        ob_end_clean();


        $message = trim($message);

        $email_address = $info_ordine->email;
        //$email_address = "vincenzo.sambucaro@nuvo.it";
        $mail = new PHPMailer;
        $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
        $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
        $mail->From 	= 'noreply@ovs.it';
        $mail->FromName = 'OVS Online Store';
        $mail->Subject 	= 'Conferma Spedizione OVS #'.$info_ordine->ordine;
        $mail->Body		= $message;

        $mail->addAddress($email_address);
        //$mail->addBCC('');
        //$mail->addBCC('');
        //$mail->addBCC('');

        if ($info_ordine->need_invoice=='true') {
            //$mail->addBCC('');
        }
        $mail->addAttachment($invoicepath);
        $mail->isHTML(true);
        $mail->send();

        $this->log->LogDebug("Inviata conferma ordine: ".$email_address." per ordine: ".$info_ordine->ordine);

       // print_r($message);


    }



}


$t = new TestConfirmOrder();
$t->export(array('100003051'));
