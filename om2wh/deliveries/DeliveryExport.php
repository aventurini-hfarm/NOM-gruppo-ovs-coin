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
require_once realpath(dirname(__FILE__))."/DeliveryXMLGenerator.php";
require_once realpath(dirname(__FILE__))."/../../omdb/DeliveryDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../../omdb/CountryDBHelper.php";


Mage::app();

class DeliveryExport {



    const DATE_FORMAT = 'Ymd'; #per la format dell'oggetto Date
    const DATE_FORMAT_SERVER = 'yyyyMMdd';


    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
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

            $sku = $line['sku'];
            $qta = (int)$line['order_quantity'];
            $desc = htmlspecialchars($line['description']);
            $subinventory = $line['subinventory'];
            $itemObj = new stdClass();
            $itemObj->sku = $sku;
            $itemObj->sku_description=$desc;
            $itemObj->qty=$qta;
            $itemObj->delivery_line_id=$delivery_line_id;
            //$itemObj->montaggio=$montaggio;
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

    public function export($lista_ordini) {


        //$lista_ordini=array($ordine_specifico);

        $fileGenerator = new DeliveryXMLGenerator();

        foreach ($lista_ordini as $record) {
            $increment_id = $record['increment_id'];
            $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
            $this->log->LogInfo("\nExport: ".$increment_id);

            $lines = MagentoOrderHelper::getOrderLineDetails($increment_id);
            //echo "\nLines: ";
            //print_r($lines);
            $lista_subinventory = $this->getListaSubinventory($order->getDwOrderNumber(), $lines);
            //echo "\nSubInvetory: ";
            //print_r($lista_subinventory);


            foreach ($lista_subinventory as $key => $lista_item) {

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



            $dbHelper = new DeliveryDBHelper();

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

                $fileGenerator->addDeliveryObject($deliveryObj);
            }



        }//ciclo for su lista ordini

        $fileGenerator->generatePickListFile();

        //print_r($xml);

        return;

    }




    public function getListaOrdiniDaExportare() {

        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('status', 'pending')
            ->addAttributeToSelect('increment_id');  //TODO verificare perchè ogni tanto è pending e altre volte pending_payment VINCENZO 2015

        $lista = $orders->getData();
       // print_r($orders->getData());
        return $lista;
    }


    public function start() {
        $list = $this->getListaOrdiniDaExportare();
        $this->export($list); //li esporta tutti
        $magHelper = new MagentoOrderHelper();

        //modifico gli stati degli ordini inviati
        foreach ($list as $order) {
            $increment_id = $order['increment_id'];
            $magHelper->setStatusProcessing($increment_id);

        }


    }

}
//Esporta Ordini

$orderManager = new DeliveryExport();
$orderManager->start();
//$listaOrdiniProcessati = $wordManager->export('100000057');

?>