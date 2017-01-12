<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 08/06/15
 * Time: 16:44
 */

require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/FileGenerator.php";
require_once realpath(dirname(__FILE__))."/../../common/CountersHelper.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();


class StockGenerator {

    private $log;
    private $config;
    private $storeId = "3737";

    public function __construct()
    {

        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/stock_export.log',KLogger::DEBUG);
        $this->storeId = $this->config->getEcommerceShopCode();


    }

    public function run($sku_array = null) {
        $this->log->LogInfo("Starting exporting stock");
        $this->writeRecordToFile($this->getProductList($sku_array));
        //$tmp= array();
        //array_push($tmp,"temp");
        //$this->writeRecordToFile($tmp);
        //print_r($sku_array);
        $this->log->LogInfo("End stock export");
    }


    function productCallback($args)
    {
        $product = Mage::getModel('catalog/product');
        $product->setData($args['row']);
        $sku=$product->getSku();

        //echo "\nidx:".$args['idx']." sku:".$sku;


        if (strpos($sku,'fake') !== false) {
            return;
        }

        $id = $product->getId();
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($id);
        $qty = $stock->getQty();


        $position = strpos($qty, ',');

        // echo "\n$qty; ".$position;

        if ($qty<0) {
            $this->log->LogWarn("Attenzione sku: ".$sku.", qty negativa: ".$qty);
            $qty = 0;
        }

        $subinventory = $product->getData('subinventory');
        //echo "\nSKU: ".$product->getSku()." , sub: ".$product->getData('subinventory')." , stock: ".$stock->getQty();

        $xml = $this->getXML($sku, $subinventory, number_format($qty, 0));
        array_push($this->content, $xml);



        $this->found = true;


    }

    private function getProductList($sku_array = null) {


        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('subinventory');
        $collection->addAttributeToSelect('prenotabile');



        $size=$collection->getSize();
        $this->log->LogInfo($size." item to stock export");

        $date = date('Y-m-dTH:i:s');

        $this->content = array();

        $ref_counter = CountersHelper::getStockReferenceNumber(date('Y'));
        $xmlns = "OV_STOCK_OUTBOUND";
        if ($sku_array)
            $xmlns = "OV_STOCK_INV_ADJ_OUTBOUND";

        $header = '<?xml version="1.0"?>
<stockList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.xmlns.com/'.$xmlns.'" dateTime="'.$date.'" reference="'.$ref_counter.'">'; //TODO SISTEMARE REFERENCE
        $footer = "</stockList>";

        array_push($this->content, $header);
        $this->found = false;

        Mage::getSingleton('core/resource_iterator')->walk($collection->getSelect(), array(array($this,'productCallback')));

//        foreach ($collection as $product) {
//
//            $sku = $product->getSku();
//
//            if (strpos($sku,'fake') !== false) {
//                continue;
//            }
//
//            //echo "\nCheck SKU: ".$sku;
//            if ($sku_array && !in_array($sku, $sku_array)) {
//                continue;
//            } // se viene passato un array significa che è un export causato da ROH ovveor rettifiche
//
//            $id = $product->getId();
//            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($id);
//            $qty = $stock->getQty();
//
//
//            $position = strpos($qty, ',');
//
//           // echo "\n$qty; ".$position;
//
//            if ($qty<0) {
//                $this->log->LogWarn("Attenzione sku: ".$sku.", qty negativa: ".$qty);
//                $qty = 0;
//
//            }
//
//            /*$prenotazione_mobile = false;
//            if ($product->getData('prenotabile')=='true')
//            {
//                $prenotazione_mobile=true;
//                $this->log->LogDebug("Export Stock prenotabile: ".$sku." , ".$product->getData('prenotabile'));
//            }
//
//            if ($prenotazione_mobile) {
//                $qty = 999;
//                echo "\nExport QTY > 999";
//            }*/
//
//
//            $subinventory = $product->getData('subinventory');
//            //echo "\nSKU: ".$product->getSku()." , sub: ".$product->getData('subinventory')." , stock: ".$stock->getQty();
//
//            $xml = $this->getXML($sku, $subinventory, number_format($qty, 0));
//            array_push($content, $xml);
//
//            /*
//            if ($sku=="006650803-000") {
//               //echo "\nECCO SKU: ".$sku." qta: ".$qty;
//               //echo "\n$xml";
//            }*/
//
//            $found = true;
//        }


        array_push($this->content, $footer);

        if (!$this->found) $this->content = null; //se non ci sono record da scrivere annulla tutto

        //print_r($content);
        return $this->content;
    }


    private function getXML($sku, $subinventory, $qty) {
        return "<stock>
<company>OV</company>
<sku>$sku</sku>
<storeid>$this->storeId</storeid>
<subinventory>$subinventory</subinventory>
<stock_level1>$qty</stock_level1>
<stock_level2>0</stock_level2>
<stock_level3>0</stock_level3>
</stock>";

    }

    private function writeRecordToFile($content) {


        if (!$content) {
            $this->log->LogWarn("Stock File is empty");
            return;
        }


        $timestamp = date('YmdHis');

       // $file_name = $timestamp."-stock_cc_it_SG_DW_".$timestamp.".xml";
        $file_name = "stock_ov_it_SG_DW_".$timestamp.".xml";

        $directory = $this->config->getStockExportOutboundDir();
        $full_name = $directory."/".$file_name;
        $this->log->LogDebug("Stock File: ".$full_name);

        $fileGenerator = new FileGenerator();
        $fileGenerator->createFile($full_name);

        $fileGenerator->writeRecord($content);
        $fileGenerator->closeFile();

        unset($content);
    }

}


