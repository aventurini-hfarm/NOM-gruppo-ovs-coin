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
require_once realpath(dirname(__FILE__))."/MagentoProductHelper.php";
require_once realpath(dirname(__FILE__))."/ProductObject.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";

class CatalogXMLAnalyzer {
    private $log;

    public function __construct($file)
    {
        $this->file = $file;
        $this->log = new KLogger('/var/log/nom/import_catalog.log',KLogger::DEBUG);
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
            if ($counter<=4) {
                continue;
            }

            $str = trim(substr($line,0, strlen($line)-1));
            //$str = $line;

            //echo "\n".$str;
            # do same stuff with the $line
            //echo "\n".substr($line,0, strlen($line)-2);
            //echo "\n!".$str."!";
            if ($str=="</product>") {
                //echo "\nFOUND";

                $xmlContent =$buffer.$str;
                //print_r($xmlContent);
                $xml = new SimpleXMLElement($xmlContent);


                $this->processProductSection($xml);
                //die;
                $buffer ="";
            } else $buffer .= $line;

        }
        fclose($fh);

    }





    public function processProductSection(SimpleXMLElement $xmlContent = null)
    {
        //$doc = new DOMDocument();
        //$doc->load($this->file);

        $xml = $xmlContent;
        $baseProductCode = $xml->baseProductCode;
        $baseProductName = $xml->baseProductName;

        //$this->log->LogDebug ("\nBaseProductCode: ".$baseProductCode);
        //$this->log->LogDebug("\nBaseProductName: ".$baseProductName);

        $obj = $xml->xpath("variations/itemFashion")[0];
        $sku = $obj->sku;
        $longDesc = $obj->longDesc;
        $prenotabile= (string)$obj->prenotabile;

        //$this->log->LogDebug("\nSKU: $sku");
        //$this->log->LogDebug("\nDesk: $longDesc");
        //$this->log->LogDebug("\nSKU: $sku, Prenotabile: $prenotabile");

        $product = new ProductObject();
        $product->baseProductCode=$baseProductCode;
        $product->baseProductName = $baseProductName;
        $product->sku = $sku;
        $product->longDesc = $longDesc;
        $product->prenotabile = $prenotabile;

        //  RINO 22/09/2016
        //  Tls
        $baseProductNames= $xml->xpath("baseProductTls/baseProductTl/baseProductName");
        $product->baseProductNameEN=$baseProductNames[0]; //$xml->baseProductTls->baseProductTl->baseProductName[0];
        $product->baseProductNameES=$baseProductNames[1]; //$xml->baseProductTls->baseProductTl->baseProductName[1];
        $product->baseProductNameIT=$baseProductNames[2]; //$xml->baseProductTls->baseProductTl->baseProductName[2];

        $longDescs = $xml->xpath("variations/itemFashion/longDescTls/longDescTl/longDesc");
        $product->longDescEN = $longDescs[0];
        $product->longDescES = $longDescs[1];
        $product->longDescIT = $longDescs[2];
        //  end Tls

        $helper = new MagentoProductHelper();
        //if ($prenotabile)
        $helper->import($product);

    }
}

//$t = new CatalogXMLAnalyzer('/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/catalog_export/inbound/20150421015027-catalog_cc_it_DW_SG_20150420231945.xml');
//$t->process();