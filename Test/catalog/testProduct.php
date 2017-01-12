<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 23:24
 */
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class testProduct {

    public function test($pId){
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $product = Mage::getModel('catalog/product')->load($pId);
        //print_r($product->getData());
        $product->setData('baseproductcode', '10');
        $product->save();
    }

    public function setStockData($sku) {


        $product  = Mage::getModel('catalog/product')->loadByAttribute('sku', '000480140-000');
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $data = $stock->getData();
        print_r($data);

        $product  = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        echo "\nSTOCK PROBLEM";
        print_r($stock);


        $stock_data=array(
            'use_config_manage_stock' => 1,
            'qty' => 9,
            'min_qty' => 0,
            'use_config_min_qty'=>1,
            'backorders'=>0,
            'use_config_backorders'=>1,
            'min_sale_qty' => 1,
            'use_config_min_sale_qty'=>1,
            'max_sale_qty' => 9999,
            'use_config_max_sale_qty'=>1,
            'is_qty_decimal' => 0,
            'backorders' => 0,
            'notify_stock_qty' => 1,
            'is_in_stock' => 1,
            'use_config_notify_stock_qty'=>1,
            'manage_stock'=>1,
            'use_config_manage_stock'=>1,
            'stock_status_changed_auto'=>0,
            'use_config_qty_increments'=>1,
            'qty_increments'=>0,
            'use_config_enable_qty_inc'=>1,
            'enable_qty_increments'=>0,
            'is_decimal_divided'=>0,
            'stock_status_changed_automatically'=>0,
            'use_config_enable_qty_increments'=>1
        );
        //print_r($stock_data);
        //$product->setData('stock_data',$stock_data); //Da commentare in caso di update

        $stockItem = Mage::getModel('cataloginventory/stock_item');
        $stockItem->assignProduct($product);
        $stockItem->setData('is_in_stock', 1);
        $stockItem->setData('qty', 1);

        $product->setStockItem($stockItem);

        // Create the initial stock item object
        $stockItem->setData('manage_stock',1);
        $stockItem->setData('is_in_stock',1);
        $stockItem->setData('use_config_manage_stock', 1);
        //$stockItem->setData('stock_id',1);
        $stockItem->setData('product_id',$product->getId());
        $stockItem->setData('qty',9);
        $stockItem->save();

        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());


            // Set the quantity
            $stockItem->setData('qty',9);
            $stockItem->save();
            $product->save();


    }
}

$t = new testProduct();
//$t->test(5528);
$t->setStockData('000480141-000');