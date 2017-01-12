<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 11:20
 */


ini_set('memory_limit', '-1');
//error_reporting(E_ERROR );
require_once realpath(dirname(__FILE__))."/../../common/ConfigManagerDM.php";
require_once realpath(dirname(__FILE__))."/ItemObject.php";
require_once realpath(dirname(__FILE__))."/MagentoInventoryHelperDM.php";

class QOHXMLAnalyzerDM {


    public function __construct($file)
    {
        $this->file = $file;
        // get store_id from file
        $magentoHelper = new MagentoHelper();
        $store_id=$magentoHelper->getStoreIdFromFile($this->file);
        $this->helper = new MagentoInventoryHelperDM($store_id);
    }

    public function process($id_magazzino)
    {
        $fh = fopen($this->file, 'r');
        $buffer = "";
        $counter = 0;
        while(!feof($fh)){
            $line = fgets($fh);
           // echo "\nLine: ".$line;
            $counter++;
            if ($counter<=3) {
                continue;
            }

            $str = trim(substr($line,0, strlen($line)-1));
            //$str = $line;

            //echo "\n".$str;
            # do same stuff with the $line
            //echo "\n".substr($line,0, strlen($line)-2);
            //echo "\n!".$str."!";
            if ($str=="</qoh>") {
                //echo "\nFOUND";

                $xmlContent =$buffer.$str;
                print_r($xmlContent);
                $xml = new SimpleXMLElement($xmlContent);


                $this->processQOHSection($xml, $id_magazzino);
                //die;
                $buffer ="";
            } else $buffer .= $line;

        }
        fclose($fh);
        $this->helper->closeProcessing();

    }



    public function processQOHSection(SimpleXMLElement $xmlContent = null, $id_magazzino)
    {
        //$doc = new DOMDocument();
        //$doc->load($this->file);

        $xml = $xmlContent;
        $qoh = new ItemObject();

        $sku = $xml->{'sku'};
        $qoh->sku = (string)$sku;

        $subinventory = $xml->{'subinventory'};

        //se subinventory non c'è lo posso gestire con $id_magazzino
        if ((string)$subinventory) {
            $qoh->subinventory = (string)$subinventory;
        } else {
            $configManager = new ConfigManagerDM();
            $prop = "subinventory.mag".$id_magazzino;
            $subinventory = $configManager->getProperty($prop);
            $qoh->subinventory = (string)$subinventory;
        }

        $onHand = $xml->{'onHand'};
        $qoh->onHand = (string)$onHand;


        $this->helper->setQOH($qoh);

    }
}

//$t = new OrderXMLAnalyzer('/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/order_export/inbound/20141023114639-order_cc_it_DW_SG_20141023094501.xml');
//$t->process();