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
require_once realpath(dirname(__FILE__))."/../omdb/ShipmentDBHelper.php";
require_once "/var/www/html/soap/ClickCollectHelper.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();

class RitiroCCEmailHelper {


    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/email_cc_sender.log',KLogger::DEBUG);

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

        $shippingDBHelper = new ShipmentDBHelper($order->getDwOrderNumber());
        $shipping_custom_attributes = $shippingDBHelper->getCustomAttributes();


        $piva = $order_custom_attributes['partitaIva'] ? "IT".$order_custom_attributes['partitaIva']: "";
        $cf = $order_custom_attributes['codiceFiscale'];

        $bill_to_info= $order->getBillingAddress();
        //print_r($order->getData());

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

        if ($shipping_custom_attributes['clickAndCollectStoreId']) {
            //si tratta di click&collect
            $infoOrdine->click_collect = true;
            $infoOrdine->negozio = $shipping_custom_attributes['clickAndCollectStoreName'];
            $infoOrdine->indirizzo_negozio = $shipping_custom_attributes['clickAndCollectAddress1']." ".$shipping_custom_attributes['clickAndCollectAddress2'];
            $infoOrdine->negozio_paese = $shipping_custom_attributes['clickAndCollectPostalCode']." ".$shipping_custom_attributes['clickAndCollectCity']." ".$shipping_custom_attributes['clickAndCollectStateCode'];
            $infoOrdine->country = $shipping_custom_attributes['clickAndCollectCountryCode'];
            $infoOrdine->orario_negozio_html = $shipping_custom_attributes['clickAndCollectStoreHoursHtml'];
            $infoOrdine->orario_negozio = $shipping_custom_attributes['clickAndCollectStoreHours'];

            $clickCollectHelper = new ClickCollectHelper();
            $data_ric=$clickCollectHelper->getCustomAttribute($order_no, 'STATUS','IN_STORE')->data_operazione;
            $infoOrdine->data_ricezione_negozio = date ('d-m-Y', strtotime($data_ric));
        } else
            $infoOrdine->click_collect = false;

        print_r($infoOrdine);
        return $infoOrdine;

    }




    public function inviaEmailRitiroCC($increment_id) {

        //return;

        $template_email 		= "/home/OrderManagement/email/template/template_email_ritiro_cc.php";

        //info cliente
        $info_cliente = $this->getInfoCliente($increment_id);
        //print_r($info_cliente->getData());

        //info ordine
        $info_ordine = $this->getInfoOrdine($increment_id);

        //echo "\nPrepara Template Email";
        ob_start();
        include($template_email);
        $message = ob_get_contents();
        ob_end_clean();
        //echo "\nMessage: ".$message;

        $message = trim($message);

        $email_address = $info_ordine->email;
        //$email_address = "nomovs@gmail.com";  //RINO  23/07/2016 TODO togliere dopo i test
        $mail = new PHPMailer;
        $mail->CharSet = "UTF-8";
        $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 15/07/2016
        $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
        $mail->From 	= 'noreply@ovs.it';
        $mail->FromName = 'OVS Online Store';
        $mail->Subject 	= 'Conferma ricezione ordine in negozio OVS #'.$info_ordine->ordine;
        $mail->Body		= $message;

        $mail->AddEmbeddedImage("/home/OrderManagement/email/template/logo.jpg", "logo_ovs", "/home/OrderManagement/email/template/logo.jpg", "base64", "image/png");
        $mail->addAddress($email_address);
        $mail->addBCC('ecommerce.tracking@ovs.it');
        $mail->addBCC('ovs@evologi.it');
        $mail->addBCC('nomovs@gmail.com');
        $mail->addBCC('support.ovs@nuvo.it');

        $mail->addBCC('ovs.confirmorder@everis.com');

        $mail->isHTML(true);
        $mail->send();

        //echo "\nInvio: ";
        $this->log->LogDebug("Inviata email ritiro cc: ".$email_address." per ordine: ".$info_ordine->ordine);

        //print_r($message);


    }


}

//$t = new RitiroCCEmailHelper();
//$t->inviaEmailRitiroCC('100003493');
