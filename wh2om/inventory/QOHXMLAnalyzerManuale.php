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
require_once realpath(dirname(__FILE__))."/MagentoInventoryHelperManuale.php";

class QOHXMLAnalyzerManuale {


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
            if ($str=="</qoh>") {
                //echo "\nFOUND";

                $xmlContent =$buffer.$str;
                //print_r($xmlContent);
                $xml = new SimpleXMLElement($xmlContent);


                $this->processQOHSection($xml);
                //die;
                $buffer ="";
            } else $buffer .= $line;

        }
        fclose($fh);

    }



    public function processQOHSection(SimpleXMLElement $xmlContent = null)
    {
        //$doc = new DOMDocument();
        //$doc->load($this->file);

        $xml = $xmlContent;
        $qoh = new ItemObject();

        $sku = $xml->{'sku'};
        $qoh->sku = (string)$sku;

        $subinventory = $xml->{'subinventory'};
        $qoh->subinventory = (string)$subinventory;

        $onHand = $xml->{'onHand'};
        $qoh->onHand = (string)$onHand;

        $helper = new MagentoInventoryHelperManuale();
        $helper->setQOH($qoh);

    }
}

$t = new QOHXMLAnalyzerManuale('/home/OrderManagement/testFiles/inventory_export/inbound/archive/qoh_cc_it_WH_SG_20150806040052.xml');
$t->process();