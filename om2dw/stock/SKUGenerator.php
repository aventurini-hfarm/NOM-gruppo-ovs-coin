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

class SKUGenerator {

    private $log;
    private $config;
    private $storeId = "4563";


    public function run() {
        //print_r($sku_array);
        $this->getProductList();

    }

    private function getProductList() {
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('subinventory');

        foreach ($collection as $product) {

            $sku = $product->getSku();
            echo "\n".$sku;
            $id = $product->getId();
            //$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($id);
            //$qty = $stock->getQty();

        }



    }

}


$t = new SKUGenerator();
$t->run();