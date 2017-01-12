<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 11:20
 */


ini_set('memory_limit', '-1');
//error_reporting(E_ERROR );
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/ItemObject.php";
require_once realpath(dirname(__FILE__))."/ShipmentObject.php";
require_once realpath(dirname(__FILE__))."/MagentoShipmentHelper.php";
require_once realpath(dirname(__FILE__))."/../../omdb/ShipmentDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../paymentgw/PaymentProcessor.php";
require_once realpath(dirname(__FILE__))."/../../dw2om/orders/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__)) . "/../../Utils/MailSender.php";

class SimulatoreShipment {



    public function process($order_no, $esito)
    {


        if ($esito == '1') {
            $magHelper = new MagentoOrderHelper();
            $status = $magHelper->getOrderStatus($order_no);

            if ($status == 'complete') {
                echo "\nOrdine già complete: ".$order_no;

                return;
            }


                echo "\nShipping Full Order";
                $helper = new MagentoShipmentHelper();
                $helper->shippingSimulator($order_no);


                $magHelper = new MagentoOrderHelper();
                echo "\nCrea parte fiscale ovvero scontrino o fatturazione";
                $magHelper->createFiscalInfo($order_no);
                echo "\nOrdine pronto per invio email conferma";
                //$magHelper->prepareConfirmOrder($order_no);

                echo "\nCrea Invoice su OM";
                $magHelper->doInvoice($order_no);

                echo "\nShipment OK: ".$order_no;


        } else {
            echo "\nEsito negativo Ordine: ".$order_no;
            $magHelper = new MagentoOrderHelper();
            $increment_id = $magHelper->getOrderIdByDWId($order_no);
            $magHelper->setStatusOnHold($increment_id);


        }

    }
}

//$t = new OrderXMLAnalyzer('/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/order_export/inbound/20141023114639-order_cc_it_DW_SG_20141023094501.xml');
//$t->process();

$t = new SimulatoreShipment();
$arrayOrderProcessor = ["00282786","00282795","00282800","00282805","00282807","00282808","00282812",
    "00282816","00282817","00282850","00282875","00282885","00282888","00282889","00282899","00282900","00282909","00282919","00282921",
    "00282939","00282963","00282967","00282970","00282976"];

foreach( $arrayOrderProcessor as $item ){
    $t->process($item, "1");
}



