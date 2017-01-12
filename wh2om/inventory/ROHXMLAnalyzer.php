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
require_once realpath(dirname(__FILE__))."/MagentoInventoryHelper.php";

class ROHXMLAnalyzer {


    private $sku_list = array();

    public function __construct($file)
    {
        $this->file = $file;

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
            if ($counter<=2) {
                continue;
            }

            $str = trim(substr($line,0, strlen($line)-1));
            //$str = $line;

            //echo "\n".$str;
            # do same stuff with the $line
            //echo "\n".substr($line,0, strlen($line)-2);
            //echo "\n!".$str."!";
            if ($str=="</roh>") {
                //echo "\nFOUND";

                $xmlContent =$buffer.$str;
                //print_r($xmlContent);
                $xml = new SimpleXMLElement($xmlContent);


                $this->processROHSection($xml);
                //die;
                $buffer ="";
            } else $buffer .= $line;

        }
        fclose($fh);

        return $this->sku_list; //lista serve per poter generare i file di stock rettificati verso DW

    }



    public function processROHSection(SimpleXMLElement $xmlContent = null)
    {
        //$doc = new DOMDocument();
        //$doc->load($this->file);

        $xml = $xmlContent;
        $qoh = new ItemObject();

        $sku = $xml->{'sku'};
        $qoh->sku = (string)$sku;

        $subinventory = $xml->{'subinventory'};
        $qoh->subinventory = (string)$subinventory;

        $quantity_delta = $xml->{'quantity_delta'};
        $qoh->quantity_delta = (string)$quantity_delta;

        $helper = new MagentoInventoryHelper();
        $helper->setROH($qoh);

        $this->sku_list[] = (string)$sku;



    }
}

//$t = new OrderXMLAnalyzer('/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/order_export/inbound/20141023114639-order_cc_it_DW_SG_20141023094501.xml');
//$t->process();