<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 25/04/15
 * Time: 13:58
 */

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/OMDBManager.php";
require_once realpath(dirname(__FILE__))."/../../common/MagentoHelper.php";

Mage::app();

class MagentoInventoryHelperSubInventory {

    private $log;
    private $con;

    public function __construct($store_id)
    {
        $this->log = new KLogger('/var/log/nom/magento_inventory_helper.log',KLogger::DEBUG);
        $this->store_id = $store_id;
        $this->con = OMDBManager::getMagentoConnection();

    }

    public static function setManualReindex() {

        $process = Mage::getSingleton('index/indexer')->getProcessByCode('cataloginventory_stock');
        $process->setMode(Mage_Index_Model_Process::MODE_MANUAL)->save();


    }

    public static function setAutomaticReindex() {

        $process = Mage::getSingleton('index/indexer')->getProcessByCode('cataloginventory_stock');
        $process->reindexEverything();
        $process->setMode(Mage_Index_Model_Process::MODE_REAL_TIME)->save();


    }


    public function setQOH(ItemObject $item) {



        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        //Mage::app()->setCurrentStore($this->store_id);
        //$product  = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->sku);
        $sku = $item->sku;
        $sql ="SELECT entity_id FROM catalog_product_entity WHERE sku='$sku'";
        $res = mysql_query($sql);
        $productRow = mysql_fetch_object($res);
        $attribute_id = 158;
        if ($productRow) {
            $subinventory = $item->subinventory;
            $id = $productRow->entity_id;
            $sql = "SELECT value FROM catalog_product_entity_varchar WHERE entity_id=$id
            AND
            attribute_id=$attribute_id";
            //echo "\nSQL1: ".$sql;
            $res = mysql_query($sql);
            $found = false;
            while ($row = mysql_fetch_object($res)) {
                //echo "\nECCOMI: ";
                    $sql = "UPDATE catalog_product_entity_varchar
                    SET value='$subinventory'
                    where entity_id=$id
                    AND
                    attribute_id=$attribute_id";
                    //echo "\nSQL2: ".$sql;
                    $res = mysql_query($sql);
                    $found = true;
           }

            if (!$found) {
            //$subinventory = $item->subinventory;
                //echo "\nSaving manuale prodotto";
                //invece del save provo con insert
                //$product->setData('subinventory',$subinventory);
                //$product->save();
                $sql ="INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value)
                VALUES (4, $attribute_id, 0, $id, '$subinventory')";
                //echo "\nSQL: ".$sql;
                $res = mysql_query($sql);
            }


        }

    }


    public function closeProcessing() {
        OMDBManager::closeConnection($this->con);
    }

}

