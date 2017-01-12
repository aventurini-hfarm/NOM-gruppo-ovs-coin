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

class IncassoManuale {

    private $log;
    public function __construct()
    {

        $this->log = new KLogger('/var/log/nom/incasso_manuale.log',KLogger::DEBUG);
    }


    public function process($dw_order_no)
    {

                //esegui la capture
                $this->log->LogDebug("Setta incasso  Manuale");
                $payment = new PaymentProcessor($dw_order_no);
                //$result = $payment->executeIncassoManuale();
                $result = $payment->executePayment();

                $magHelper = new MagentoOrderHelper();
                $this->log->LogDebug("Crea parte fiscale ovvero scontrino o fatturazione");
                $magHelper->createFiscalInfo($dw_order_no);

    }
}

//$t = new OrderXMLAnalyzer('/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/order_export/inbound/20141023114639-order_cc_it_DW_SG_20141023094501.xml');
//$t->process();

$arrayOrderProcessor = ["00216975", "00216885", "00216872", "00216982"];

foreach( $arrayOrderProcessor as $item ){
	$t = new IncassoManuale();
	$t->process($item);
}