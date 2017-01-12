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
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/OMDBManager.php";
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class DeliveryXMLAnalyzer {
    private $log;

    public function __construct($file)
    {
        $this->file = $file;
        $this->log = new KLogger('/var/log/nom/import_delivery.log',KLogger::DEBUG);
    }

    public function process()
    {
        $fh = fopen($this->file, 'r');
        $buffer = "";
        $counter = 0;
        while(!feof($fh)){
            $line = fgets($fh);
           // echo "\nLine: ".$line;
            $counter++;
            if ($counter<=1) {
                continue;
            }

            $str = trim(substr($line,0, strlen($line)-1));
            //$str = $line;

            //echo "\n".$str;
            # do same stuff with the $line
            //echo "\n".substr($line,0, strlen($line)-2);
            //echo "\n!".$str."!";
            if ($str=="</delivery_header>") {
                //echo "\nFOUND";

                $xmlContent =$buffer.$str;
                //print_r($xmlContent);
                $xml = new SimpleXMLElement($xmlContent);


                $this->processDeliverySection($xml);
                //die;
                $buffer ="";
            } else $buffer .= $line;

        }
        fclose($fh);

    }





    public function processDeliverySection(SimpleXMLElement $xmlContent = null)
    {
        //$doc = new DOMDocument();
        //$doc->load($this->file);

        $xml = $xmlContent;

        $obj = new stdClass();
        $obj->delivery_id = (string)$xml->delivery_id;
        $billingXml = $xml->xpath("bill_to_info")[0];
        $shippingXml = $xml->xpath("ship_to_info")[0];

        $obj->order_number = (string)$xml->order_number;
        $obj->bill_to_email = (string)$billingXml->bill_to_email;
        $obj->bill_to_cust_number = (string)$billingXml->bill_to_cust_number;
        $obj->subinventory= (string)$xml->subinventory;
        $obj->ship_to_cust_number = (string)$shippingXml->ship_to_cust_number;
        $obj->ship_to_email = (string)$shippingXml->ship_to_email;

        $this->updateDeliveryId($obj);
        $this->updateCustomerId($obj);
    }

    public function updateDeliveryId($obj) {
        $con = OMDBManager::getConnection();
        $delivery_id = $obj->delivery_id;
        $order_number = $obj->order_number;
        $subinventory = $obj->subinventory;
        //$sql = "UPDATE delivery SET delivery_id='$delivery_id', subinventory='$subinventory', esito=1, status=0
        //WHERE order_number='$order_number'";
        $sql = "INSERT INTO delivery (delivery_id, order_number, subinventory, esito, status)
        VALUES ('$delivery_id', '$order_number', '$subinventory', 0, 1)";

        $res = mysql_query($sql);
        OMDBManager::closeConnection($con);
    }

    public function updateCustomerId($obj) {
        print_r($obj);
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());

        if ($obj->bill_to_email) {
            echo "\nAggiorno billing sg_user_id: ".$obj->bill_to_cust_number;
            $customer->loadByEmail($obj->bill_to_email);
            $customer->setData('sg_user_id',  $obj->bill_to_cust_number);
            $customer->save();
        }
        if ($obj->ship_to_email) {
            echo "\nAggiorno shipping sg_user_id: ".$obj->ship_to_cust_number;
            $customer->loadByEmail($obj->ship_to_email);
            $customer->setData('sg_user_id', $obj->ship_to_cust_number);
            $customer->save();
        }

    }
}

//$t = new CatalogXMLAnalyzer('/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/catalog_export/inbound/20150421015027-catalog_cc_it_DW_SG_20150420231945.xml');
//$t->process();