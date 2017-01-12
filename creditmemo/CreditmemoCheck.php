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
require_once realpath(dirname(__FILE__)) . "/../Utils/mailer/PHPMailerAutoload.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();


class CreditMemoCheck {

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


    public function checkCreditMemoProcess($start_date, $end_date, $bill_numbers) { // RINO 9/7/2016

        $this->log->LogInfo("Start");
        if ($bill_numbers)
            $lista_creditmemo = $this->getListaCreditMemoByScontrino( $bill_numbers );
        else {
            $lista_creditmemo = $this->getListaCreditMemoDaVerificare( $start_date, $end_date );
        }

        echo "\nLISTA CREDIT MEMO:";
        print_r($lista_creditmemo);

        $all_records= array();
        if ($lista_creditmemo) {


            $enti=CountryDBHelper::getEnti();
            foreach ($enti as $ente) {
                $this->checkCreditmemo($ente,$lista_creditmemo,'1');
            }


        }
        else
            $this->log->LogInfo("\nNessun ordine da esportare");

    }

    private function checkCreditmemo($ente, $lista_creditmemo, $sopra_soglia) {

        $codice_ente = $ente->codice_ente;


        $start_date = date('d/m/Y H:i:s');


        $codice_cassa = $this->config->getEcommerceShopCodiceCassa();

        $totale_globale=0;
        $totale_scontrini=0;




        /**
         * SEZIONE CREDITMEMO
         */

        $lista_fallimenti=null;

        foreach ($lista_creditmemo as $obj) {
            $order_id = $obj->order_id;
            $creditmemo_id = $obj->creditmemo_id;

            $info_creditmemo = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);
            $increment_id_creditmemo = $info_creditmemo->increment_id;

            $order = Mage::getModel('sales/order')->load($order_id);

            $billingAddress = $order->getBillingAddress();                                                  //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $billingAddressCountryid = strtolower($billingAddress->getCountryId());                         //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $orderCountry = CountryDBHelper::getCountryDetails($billingAddressCountryid);                   //Rino 12/07/2016 gestione multicontry e sopra-soglia
            $iva = $orderCountry->iva;

            $totale_controllo = 0;

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





                //$codice_cliente_dw = $order->getDwCustomerId();
                $order_no = $order->getDwOrderNumber();
                $data_ordine = $order->getCreatedAt();
                $newDate_ordine = date("d/m/Y H:i:s", strtotime($order->getDwOrderDatetime()));

                $trx_date = date_format(date_create_from_format('d/m/Y', $info_creditmemo->bill_date), 'Y-m-d');//In realtà per le note di credito occorre prendere la data dello scontrino di nota di credito


                $payment = $order->getPayment();
                $payment_method_selected = $payment->getMethod();


                $tmp_lista = array();

                $esenziona_iva = '0'; //richiesta da Zennaro il 30/06/2015
                $newDate_trx_date = date("d/m/Y H:i:s", strtotime($trx_date));


                $crdetails = CreditMemoHelper::getCreditMemoDetails($creditmemo_id);
                //TRX DISCOUNT
                $discount_value = $crdetails->discount_value;

                if ($discount_value) {

                    $valore_promo = number_format($discount_value * 1, 2);
                    $valore = str_pad(str_replace('.','', $valore_promo ),7,'0',STR_PAD_LEFT);
                    $valore = str_pad(str_replace(',','', $valore ),7,'0',STR_PAD_LEFT);

                    $totale_controllo -= $discount_value;
                }

                //ITEM_STOCK
                //$lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
                $lines = CreditMemoHelper::getCreditMemoItems($creditmemo_id);

                foreach ($lines as $line) {
                    //print_r($line);
                    $sku = $line->sku;
                    //$qty = -1 * $line->qty;

                    $qty =  1 * $line->qty; //26102015 -> valore positivo secondo Zennaro
                    $bp = number_format($line->base_price + $line->tax_amount,2);
                    //$bp = number_format($line->base_total,2); //03112016
                    $dv = number_format($line->discount_value,2);
                    $unit_price = str_pad(str_replace('.','', $bp),7,'0',STR_PAD_LEFT);
                    $unit_price = str_pad(str_replace(',','', $unit_price),7,'0',STR_PAD_LEFT);
                    $discount_value = str_pad(str_replace('.','', $dv),7,'0',STR_PAD_LEFT);
                    $discount_value = str_pad(str_replace(',','', $discount_value),7,'0',STR_PAD_LEFT);

                    $item_dw_promo_id = '';
                    $item_dw_extra_points = '';
                    $item_dw_return_points = '';


                    $totale_controllo += ( ($bp * $qty) - $dv);
                    //echo "\nTotale_controllo: ".$totale_controllo;


                }



                //ITEM_FEE Spedizione la prende dalla nota di credito. Se c'è va quindi riaccreditato il trasporto


                //$shipping_amount = $crdetails->shipping_amount * -1;
                $shipping_amount = $crdetails->shipping_amount; //26102015 -> valore positivo secondo Zennaro
                //echo "\nShipping_amount_credit: ".$shipping_amount;
                $shippingAmount = number_format($shipping_amount,2);
                $shippingAmount_fmt = str_pad(str_replace('.','', $shippingAmount),7,'0',STR_PAD_LEFT);

                $shippingDiscount = number_format(0,2);
                $shippingDiscount_fmt = str_pad(str_replace('.','', $shippingDiscount),7,'0',STR_PAD_LEFT);


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



                if (round($totale_controllo,2) != round($crdetails->grand_total,2)) {
                    $riga = "Attenzione squadratura resi ($increment_id_creditmemo)/($creditmemo_id)- ($trx_header_id): totale_controllo=".$totale_controllo." , totale_ordine:".$crdetails->grand_total;
                    echo "\n".$riga."\n";
                    $lista_fallimenti[] = $riga;

                }

            } //end if

        } //end for CREDIT MEMO

        if ($lista_fallimenti) {

            $message =  implode("\n\r", $lista_fallimenti);
            $email_address = "vincenzo.sambucaro@h-farm.com";

            $mail = new PHPMailer;
            $mail->CharSet = "UTF-8";
            $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
            $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
            $mail->From 	= 'noreply@ovs.it';
            $mail->FromName = 'OVS Online Store';
            $mail->Subject = "Errori note di credito";

            $mail->Body		= $message;

            $mail->addAddress($email_address);

            $mail->addBCC('alberto.botti@h-farm.com');
            $mail->addBCC('michele.fabbri@h-farm.com');
            $mail->addBCC('support@hevologi.it');
            $mail->send();
        }
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

    private function getListaCreditMemoDaVerificare( $start = null, $end = null) {

        $con = OMDBManager::getMagentoConnection();


        $sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE created_at between '$start' and '$end'";

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



    private function getListaCreditMemoByScontrino( $bill_numbers) {

        $con = OMDBManager::getMagentoConnection();
        $lista = array();
        echo "\nExport Lista Note di credito";
        foreach ($bill_numbers as $bill_number) {
            $sql ="SELECT order_id, entity_id FROM sales_flat_creditmemo WHERE bill_number = '$bill_number'";
            echo "\nSQL: ".$sql;

            $res = mysql_query($sql);

            while ($row = mysql_fetch_object($res)) {
                $obj = new stdClass();
                $obj->creditmemo_id = $row->entity_id;
                $obj->order_id = $row->order_id;
                echo "\nCredit Memo da processare: c_id".$row->entity_id." , o_id".$row->order_id." , bill_number: ".$bill_number;
                $lista[] = $obj;
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

    public function readLastTime() {
        $myfile = fopen("controlfile.txt", "r");
        if (!$myfile) return null;
        $lasttime =  fgets($myfile);
        fclose($myfile);
        return $lasttime;
    }

    public function writeLastTime($time) {
        $myfile = fopen("controlfile.txt", "w");
        fwrite($myfile, $time);
        fclose($myfile);

    }

}

//TODO METTERE LA DATA AUTOMATICA
$t = new CreditMemoCheck();
/*$start_date="2016-08-08 00:00:00";
$end_date="2016-08-08 23:59:59";
*/



// MANUALE X NOTE DI CREDITO
//$lista_bill_number=array(151556,151557,151558,151559,151560,151561,151562,151563,151564,151565,151566,151567,151568,151569,151570,151571,151572,151573,151574,151575,151576,151577,151578,151579,151580,151581,151582,151583,151584,151585,151586,151587,151588,151589,151590,151591,151592,151593,151594,151595,151596,151597,151707,151708,151709,151710,151711,151712,151713,151714,151715,151716,151717,151718,151719,151720,151721,151722,151723,151724,151725,151730,151731,151732,151733,151734,151735,151736,151737,151738,151739,151740,151742,151743,151892,151893,151894,151895,151896,151897,151898,151899,151900,151901,152108,152109,152110,152111,152588,152703,152704,152705,152706,152707,152708,152709,152710,152711,152712,152713,152714,152715,152716,152717,152718,152719,152720,152721,152722,152723,152724,152725,152726,152727,152728,152730,152731,152732,152733,152734,153484,153485,153486,153487,153488,153489,153490,153491,153492,153493,153494,153496,153497,153498,153499,153500,153501,153502,153503,153504,153505,153506,153507,153508,153509,153510,153511,153513,153514,153515,153516,153517,153520,153521,153522,153523,153524,153525,153526,153527,153528,153529,151890,151891,152729,153495,153512,153518,153519);
//$lista_bill_number=array(153974,153975,153976,153977,153978,153980,153981,153982,153983,153984,153986,153987,153988,153990,153991,153992,153993,153994,153995,153996,153998);
//$lista_bill_number=array(153530);
//$t->exportManualeNoteDicredito($lista_bill_number);

$lista_scontrini=array("173882",
    "173898",
    "173900",
    "173904",
    "173927",
    "173939",
    "173943",
    "171147",
    "174652",
    "175764",
    "176848",
    "176859",
    "176863",
    "176876",
    "176877",
    "176878",
    "176880",
    "176881",
    "176882",
    "176883",
    "176889",
    "176892",
    "176893",
    "176894",
    "176896",
    "176898",
    "176899",
    "176900",
    "177676",
    "178086",
    "178177",
    "178300",
    "178402",
    "178523",
    "178527",
    "178541",
    "178554",
    "178564",
    "178568");
//$t->checkCreditMemoProcess(null, null, $lista_scontrini); //se voglio esportare solo alcuni scontrini


$start_date = $t->readLastTime();
if (!$start_date) $start_date = date('Y-m-d H:i:s');

//$start_date = date('Y-m-d 00:00:00');
$end_date = date('Y-m-d H:i:s');
echo "\nStart time: ".$start_date." , end: ".$end_date;
$t->checkCreditMemoProcess($start_date, $end_date, null);
$t->writeLastTime($end_date);



//$t->exportPerEnte($start_date, $end_date, $start_date_cm, $end_date_cm);


