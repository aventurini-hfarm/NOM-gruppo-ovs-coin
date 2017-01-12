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




                $customerId = $order->getCustomerId();
                $customer = Mage::getModel('customer/customer')->load($customerId);



                $shipping_method_selected = $order->getData('shipping_method');
                switch ($shipping_method_selected) {
                    case "excellence_Forniture":
                        $deliveryObj->shipping_service='Forniture';
                        break;
                    case "smashingmagazine_mycarrier_standard":
                        $deliveryObj->shipping_service='ClickAndCollect';
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

                $deliveryObj->customer = $customer;
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

$obj = new DeliveryExport();
//$orderManager->start();
$helper = new MagentoOrderHelper();


//$ordini= array("00265033","00265034","00265035","00265036","00265037","00265038","00265039","00265040","00265041","00265042","00265043","00265044","00265045","00265047","00265048","00265054","00265056","00265057","00265058","00265059","00265060","00265061","00265062","00265063","00265064","00265065","00265066","00265067","00265068","00265069","00265072","00265074","00265075","00265076","00265077","00265078","00265080","00265081","00265082","00265083","00265084","00265085","00265086","00265087","00265089","00265090","00265091","00265092","00265093","00265094","00265095","00265096","00265130","00265131","00265132","00265133","00265134","00265135","00265136","00265138","00265140","00265141","00265143","00265144","00265145","00265146","00265147","00265148","00265149","00265150","00265151","00265152","00265154","00265155","00265156","00265157","00265158","00265161","00265169","00265177","00265182","00265183","00265184","00265185","00265186","00265187","00265219","00265220","00265222","00265223","00265224","00265226","00265227","00265228","00265229","00265230","00265231","00265232","00265233","00265234","00265235","00265236","00265238");
//$ordini= array("00265472","00265334", "00265436", "00265237", "00265079", "00265160", "00265159", "00265153", "00265142");
//$ordini= array("00266424");
$lista=array();
foreach ($ordini as $order) {
    $dw_order_id = $helper->getOrderIdByDWId($order);

    if ($dw_order_id) {
        echo "\nOrder_ID: " . $dw_order_id;
        array_push($lista,array('increment_id' => $dw_order_id));
    } else {
        echo "\nOrder_ID: DW order assente: " . $order;
    }

}
    $listaOrdiniProcessati = $obj->export($lista);
?>