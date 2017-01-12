<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 01/05/15
 * Time: 11:04
 */

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
require_once realpath(dirname(__FILE__))."/../../dw2om/orders/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__))."/../../common/CountersHelper.php";
require_once realpath(dirname(__FILE__))."/DeliveryObject.php";
require_once realpath(dirname(__FILE__))."/DeliveryXMLGeneratorDM.php";
require_once realpath(dirname(__FILE__))."/../../omdb/DeliveryDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../omdb/DeliveryExportDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManagerDM.php";
require_once realpath(dirname(__FILE__))."/../../omdb/CountryDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../omdb/ShipmentDBHelperDM.php";
require_once realpath(dirname(__FILE__)) . "/../../Utils/mailer/PHPMailerAutoload.php";

Mage::app();

class DeliveryExportDM {



    const DATE_FORMAT = 'Ymd'; #per la format dell'oggetto Date
    const DATE_FORMAT_SERVER = 'yyyyMMdd';


    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManagerDM();
        $this->log = new KLogger('/var/log/nom/delivery_export.log',KLogger::DEBUG);

    }



    //dal dettaglio ordine magento capisco quanti subinventory ci sono
    private function getListaSubinventory($dw_order_no, $lines) {
         $lista_subinventory = array();
        foreach ($lines as $line) {
            $delivery_line_id=CountersHelper::getDeliveyLineId();
            //$montaggio="N";

            /*if ($line['item_has_options']=='1') {
                //verifico se c'è il montaggio
                $orderDbHelper = new OrderDBHelper($dw_order_no);
                $options = $orderDbHelper->getItemOptions($line['sku']);
                foreach ($options as $option) {
                    if ( ($option->option_key=='lineitem-text') && ($option->option_value=='Montaggio: SI') ) {$montaggio="S";}
                }

            }*/
           // print_r($lines);
            $sku = $line['sku'];
            $qta = (int)$line['order_quantity'];
            $desc = htmlspecialchars($line['description']);
            $subinventory = $line['subinventory'];
            $itemObj = new stdClass();
            $itemObj->sku = $sku;
            $itemObj->sku_description=$desc;
            $itemObj->qty=$qta;
            $itemObj->delivery_line_id=$delivery_line_id;
            $itemObj->row_total = $line['row_total'];
            $itemObj->discount_value = $line['discount_value'];
            //$itemObj->montaggio=$montaggio;
            if (!$subinventory) {
                echo "\nAttenzione prodotto senza subinventory: ".$sku;
                return null;
            };
            $itemObj->subinventory=$subinventory;

            if (!array_key_exists($subinventory, $lista_subinventory))
                $lista_subinventory[$subinventory] = array();

            $tmp = $lista_subinventory[$subinventory];
            array_push ($tmp, $itemObj);
            $lista_subinventory[$subinventory] = $tmp;

        }
        //mi ritorna per ogni subinventory la lista di item
        return $lista_subinventory;

    }

    /**
     * Se ci sono due liste allora posso ripartire sul totale di ogni lista
     * Se la lista è una sola allora dovrei togliere dallo sconto da ripartire quello già messo nella lista delivery positive e quindi tutto lo sconto va inputato
     * su una singola delivery
     * @param $totale_ordine
     * @param $sconto_complessivo
     * @param $lista_item
     * @return stdClass
     */
    public function ripartoSpese($totale_righe_ordine, $sconto_complessivo, $lista_item, $lista_subinventory_positive, $dw_order_number) {

        $con = OMDBManager::getConnection();
        foreach ($lista_subinventory_positive as $subinventory) {
            $sql = "SELECT * FROM delivery_export WHERE dw_order_number='$dw_order_number' AND subinventory='$subinventory' AND status=1";
            $res = mysql_query($sql);
            $totale_sconto_merce_spedita = 0;
            while ($row = mysql_fetch_object($res)) {
                $totale_sconto_merce_spedita += $row->sconto;
            }
        }
        $totale_righe  = 0;
        foreach ($lista_item as $item) {
            echo "\nITEM\n";
            print_r($item);
            echo "\nRiparto riga: ".$item->sku." , ".$item->row_total." - ".$item->discount_value;
            $totale_righe += $item->row_total- $item->discount_value;
        }

        //$sconto_da_ripartire = $sconto_complessivo-$totale_sconto_merce_spedita;
        $sconto_da_ripartire = $sconto_complessivo;
        echo "\nTotale righe: ".$totale_righe;
        echo "\nSconto COmplessivo: ".$sconto_complessivo;
        echo "\nSconto Da Ripartire: ".$sconto_da_ripartire;
        echo "\nTotale righe ordine: ".$totale_righe_ordine;


        if ($sconto_complessivo > $totale_righe_ordine) $sconto_da_ripartire = $totale_righe;
        $valore_sconto_ripartito = ($sconto_da_ripartire/$totale_righe_ordine) * $totale_righe;

        $obj = new stdClass();
        $obj->valore_sconto_ripartito = round($valore_sconto_ripartito,2);
        $obj->totale_righe = $totale_righe;

        echo "\nValore sconto ripartito: ".$valore_sconto_ripartito;

        return $obj;
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

    public function calcolaTotaleRigheOrdine($lines) {
        $totale_righe = 0;
        foreach ($lines as $line) {
            $totale_righe += ($line['row_total'] - $line['discount_value']);
        }

        return $totale_righe;
    }

    public function export($lista_ordini) {


        $lista_eseguiti = array();
        $subinventory_mag1 = $this->config->getProperty("subinventory.mag1");
        $subinventory_mag2 = $this->config->getProperty("subinventory.mag2");

        $fileGenerator1 = new DeliveryXMLGeneratorDM($subinventory_mag1); //Magazzino 1 - subinventory1
        $fileGenerator2 = new DeliveryXMLGeneratorDM($subinventory_mag2); //Magazzino 2 - subinventory2

        $dbHelper = new DeliveryDBHelper();
        $dbExportHelper = new DeliveryExportDBHelper();

        foreach ($lista_ordini as $record) {
            $increment_id = $record['increment_id'];
            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
            $this->log->LogInfo("\nExport: ".$increment_id);

            //if ($order->getDwOrderNumber()!='50094219') continue;
            $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
            $totale_righe_ordine = $this->calcolaTotaleRigheOrdine($lines);
            echo "\nTotale Righe Ordine: ".$totale_righe_ordine;

            //echo "\nLines: ";
            //print_r($lines);
            $lista_subinventory = $this->getListaSubinventory($order->getDwOrderNumber(), $lines);
            if (!$lista_subinventory) {
                //significa errore
                $email_address = "vincenzo.sambucaro@h-farm.com";

                $mail = new PHPMailer;
                $mail->CharSet = "UTF-8";
                $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
                $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
                $mail->From 	= 'noreply@ovs.it';
                $mail->FromName = 'OVS Online Store';
                $mail->Subject = "Errore Ordine ".$order->getDwOrderNumber();

                $mail->Body		= "Ci sono articoli senza subinventory nell'ordine";

                $mail->addAddress($email_address);

                //$mail->addBCC('alberto.botti@h-farm.com');
                $mail->addBCC('michele.fabbri@h-farm.com');
                $mail->addBCC('support@hevologi.it');
                $mail->send();

                continue;
            }

            $lista_eseguiti[] = $increment_id;
            $lista_subinventory_delivery_negative = $dbExportHelper->getDeliveriesByStatus($order->getDwOrderNumber(),-1);
            echo "\nLista delivery negative:";
            print_r($lista_subinventory_delivery_negative);
            foreach ($lista_subinventory_delivery_negative as $item) {
                //$dbExportHelper->deleteDelivery($order->getDwOrderNumber(), $item); //cancello le delivery negative
            }

            //$lista_subinventory_delivery_positive = $dbExportHelper->getDeliveriesByStatus($order->getDwOrderNumber(),1);
            $lista_subinventory_delivery_positive = $dbExportHelper->getDeliveriesDaNonInviare($order->getDwOrderNumber());

            echo "\nLista delivery Positive";
            print_r($lista_subinventory_delivery_positive);


            foreach ($lista_subinventory as $key => $lista_item) {
                echo "\nAnalisi sub inventory: ".$key;
                if (in_array($key, $lista_subinventory_delivery_positive)) continue; //se l'ordine  è nella lista di delivery positive non lo ritrasmetto

                $deliveryObj = new DeliveryObject();



            /*
            $customerId = $order->getCustomerId();
            $customer = Mage::getModel('customer/customer')->load($customerId);
            */





            $shipping_method_selected = $order->getData('shipping_method');
            switch ($shipping_method_selected) {
                case "excellence_Forniture":
                    $deliveryObj->shipping_service='Forniture';
                    break;
                case "smashingmagazine_mycarrier_standard":
                    $deliveryObj->shipping_service='ClickAndCollect';
                    break;
                case "Express":
                    $deliveryObj->shipping_service='Express';
                    break;
                default:
                    $deliveryObj->shipping_service='Standard';
            }

            $payment = $order->getPayment();
            //print_r($payment->getData());

            $payment_method_selected = $payment->getData('method');
            //echo "\nPayment Method: ".$payment_method_selected;

            //print_r($order->getData());


            $order_shipping_address= $order->getShippingAddress();

            /*  RINO 21/07/2016 in OVS si usa estero_light
             * $deliveryObj->carrier=$order_shipping_address->getData('carrier');
            $country_id = $order_shipping_address->country_id;
            if ($country_id!='IT') {
                $cDetails = CountryDBHelper::getCountryDetails($country_id);
                if (!$cDetails) {
                    $corriere = "UPS";
                }
                $deliveryObj->carrier = $cDetails->corriere;
                 //print_r($order_shipping_address->getData());
            }
            */

            /*  RINO 21/07/2016 in OVS si usa estero_light */
            $country_id = $order_shipping_address->country_id;
            $cDetails = CountryDBHelper::getCountryDetails($country_id);
            $deliveryObj->carrier = $cDetails->corriere;
             /*  RINO 21/07/2016 in OVS si usa estero_light */

            $order_billing_address= $order->getBillingAddress();
            //print_r($order_billing_address->getData());

            $deliveryObj->ship_to_info = $order_shipping_address;
            $deliveryObj->bill_to_info = $order_billing_address;

            //$deliveryObj->customer = $customer; //27-10-2016
            $deliveryObj->customer_email =  $order->getData('customer_email'); //27-10-2016
            $deliveryObj->customer_no = $order->getData('dw_customer_id'); //27-10-2016
            //$deliveryObj->customer_no = $order->getId();

            $delivery_date = date(self::DATE_FORMAT);
            $deliveryObj->delivery_date = $delivery_date;

            $deliveryObj->order_date = $order->getCreatedAtDate()->toString(self::DATE_FORMAT_SERVER);
            $deliveryObj->payment_method = $payment_method_selected;

            $deliveryObj->delivery_type = 'O';
            $deliveryObj->order_number = $order->getDwOrderNumber();

            $deliveryObj->inventory_code = "OIT";
            $deliveryObj->storeid = $this->config->getEcommerceShopCode();

            $deliveryObj->brand="OV";
            $deliveryObj->ddt_lang="US";

            //modifica richiesta da Zennaro il 07072015
            $orderValue = number_format($order->getBaseGrandTotal(),2);
            //$orderValue_fmt = str_pad($orderValue, 5,'0',STR_PAD_LEFT);
            $orderValue_fmt = $orderValue;

            $deliveryObj->pay_amount=$orderValue_fmt;

            switch ($payment_method_selected) {
                case "paypal_standard" :
                    $deliveryObj->payment_method="PAYPAL";
                    break;
                case "cashondelivery" :
                    $deliveryObj->payment_method="CONTANTI";
                    break;
                case "free" :
                    $deliveryObj->payment_method="CHIOSCO";
                    break;
                default:
                    $deliveryObj->payment_method="CC";
                    break;
            }




                $delivery_lines = array();
                $deliveryObj->subinventory = $key;
                $deliveryObj->delivery_id = CountersHelper::getDeliveyId();

                foreach ($lista_item as $itemObj) {
                    //$delivery_line_id=CountersHelper::getDeliveyLineId();
                    array_push($delivery_lines, $itemObj);
                }//for $line ordine

                $deliveryObj->delivery_lines = $delivery_lines;

                $dbHelper->addDelivery($deliveryObj->delivery_id, $deliveryObj->order_number, $key);


                $deliveryObj->order_number = ltrim($order->getDwOrderNumber(),'0');  //RINO 21/07/2016 remove left zeros per esportazione sul xml

                $deliveryObj->order_number_not_trimmed = $order->getDwOrderNumber();
                echo "\nRiparto spese ordine: ".$order->getDwOrderNumber();
                echo "\nGrand Total: ".$order->getBaseGrandTotal();
                echo "\nDiscount: ".$order->getDiscountAmount();

                $obj = $this->ripartoSpese($totale_righe_ordine, $order->getDiscountAmount(), $lista_item , $lista_subinventory_delivery_positive, $order->getDwOrderNumber());
                $shippingChargeObj = $this->getShippingCharge($order->getIncrementId());
                //echo "\nShipping Amount: ".$shippingChargeObj->total;
                //print_r($shippingChargeObj);
                $spese_spedizione = ($shippingChargeObj->total)/2;

                echo "\nTotale_righe: ".$obj->totale_righe.", spese: ".$spese_spedizione.", ".$obj->valore_sconto_ripartito;
                if (sizeof ($lista_subinventory)>1) {
                    $deliveryObj->totale_ordine_ripartito = round($obj->totale_righe + $spese_spedizione - $obj->valore_sconto_ripartito,2);
                    $deliveryObj->totale_righe = round($obj->totale_righe,2);
                    $deliveryObj->spese_spedizione_ripartite = round($spese_spedizione,2);
                    $deliveryObj->valore_sconto_ripartito = round($obj->valore_sconto_ripartito,2);
                }
                else {
                    $deliveryObj->totale_ordine_ripartito = round($order->getBaseGrandTotal(),2);
                    $deliveryObj->totale_righe = round($order->getBaseGrandTotal(),2);
                    $deliveryObj->spese_spedizione_ripartite = $shippingChargeObj->total;
                    $deliveryObj->valore_sconto_ripartito = $order->getDiscountAmount();

                }

                $dbExportHelper->addDelivery($deliveryObj);

                if ($key==$subinventory_mag1)
                    $fileGenerator1->addDeliveryObject($deliveryObj);
                else
                    $fileGenerator2->addDeliveryObject($deliveryObj);
            }

            $this->checkIfOrderClosed($order->getDwOrderNumber());

        }//ciclo for su lista ordini

        $fileGenerator1->generatePickListFile();
        $fileGenerator2->generatePickListFile();

        //print_r($xml);



        return $lista_eseguiti;

    }


    public function checkIfOrderClosed($dw_order_number){

        $magHelper = new MagentoOrderHelper();
        $status = $magHelper->getOrderStatus($dw_order_number);

        if ($status == 'complete') {
            $this->log->LogWarn("Ordine già complete: ".$dw_order_number);
            return;
        }

        $dbExportHelper = new DeliveryExportDBHelper();
        $shipmentDbHelper = new ShipmentDBHelperDM();
        if ($shipmentDbHelper->isOrderShipped($dw_order_number)) {
            //se inviato tutto allora posso mandare anche la ricevuta
            //Occorre verificare che non venga inviato due volte l'email all'utente per la delivery1 nel caso la delivery2 risulta vuota
            //al secondo passaggio

            $this->log->LogDebug("Shipping Full Order");
            $helper = new MagentoShipmentHelper();
            $obj = new stdClass();
            $obj->order_no = $dw_order_number;
            $helper->shippingFullOrder($obj);

            //esegui la capture
            $this->log->LogDebug("Esegui Capture");
            $payment = new PaymentProcessor($dw_order_number);
            $result = $payment->executePayment();

            if (!$result) {
                $this->log->LogError("Metto ordine in pending payment: ".$dw_order_number);
                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($dw_order_number);
                $magOrderHelper->setStatusPendingPayment($increment_id);
                return;

            }
            echo "\nOrdine pronto per essere completato\n";
            $magHelper = new MagentoOrderHelper();
            $this->log->LogDebug("Crea parte fiscale ovvero scontrino o fatturazione");
            $magHelper->createFiscalInfo($dw_order_number);
            $this->log->LogDebug("Ordine pronto per invio email conferma");
            $magHelper->prepareConfirmOrder($dw_order_number, -1, 3); //significa che una delivery non viene più inviata

            $this->log->LogDebug("Crea Invoice su OM");
            $magHelper->doInvoice($dw_order_number);

            $this->log->logDebug('Chiuso Ordine perchè delivery completate: '.$dw_order_number);

        } else {
            $this->log->logDebug('Ordine non può essere chiuso: '.$dw_order_number);
        }

        return;
    }




    public function getListaOrdiniDaExportare() {

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('status', 'pending')
            ->addAttributeToSelect('increment_id');

        $lista = $orders->getData();
         print_r($orders->getData());
        return $lista;
    }


    public function start() {
        $list = $this->getListaOrdiniDaExportare();
        $lista_eseguiti = $this->export($list); //li esporta tutti
        $magHelper = new MagentoOrderHelper();

        //modifico gli stati degli ordini inviati
        foreach ($lista_eseguiti as $increment_id) {
            //$increment_id = $order['increment_id'];
            $magHelper->setStatusProcessing($increment_id);

        }


    }

}
//Esporta Ordini

$deliveryManager = new DeliveryExportDM();
$deliveryManager->start();
//$listaOrdiniProcessati = $wordManager->export('100000057');

?>