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

class MagentoInventoryHelper {

    private $log;
    //private $con;

    public function __construct($store_id)
    {
        $this->log = new KLogger('/var/log/nom/magento_inventory_helper.log',KLogger::DEBUG);
        $this->store_id = $store_id;
        //$this->con = OMDBManager::getMagentoConnection();  //RINO 09/08/2016

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

    public function getQTAProcessingOrdersForQOH($item) {

        //cerca gli ordini che sono in processing e che hanno quello specifico prodotto . Tolgo le QTA e mando indietro il risultato
        $con = OMDBManager::getMagentoConnection();
        $sql ="SELECT qty_ordered FROM sales_flat_order_item as item, sales_flat_order as ord WHERE
        item.order_id = ord.entity_id AND (ord.status='processing' OR ord.status='holded') AND item.sku='$item->sku'";  // RINO 11/10/2016 aggiunto status holded
        //echo "\nSQL: ".$sql;

        $res = mysql_query($sql);
        $qta_processing = 0;
        while ($row = mysql_fetch_object($res)) {
            $qta_processing = $qta_processing + intval($row->qty_ordered);
        }

        OMDBManager::closeConnection($con);
        return $qta_processing;
    }

    public function setQOH(ItemObject $item) {

        //AGggiorna descrizione (nota che tabella per le descerizione è catalog_product_entity_text)



        /*
          $con = DBUtil::getMagentoConnection();

        $sql="UPDATE catalog_product_entity_varchar v
		INNER JOIN catalog_product_entity c
		ON c.entity_id=v.entity_id
		SET v.value='$data_disp'
		WHERE v.attribute_id=280 AND c.sku='$t_sku'";
        $res = mysql_query($sql);
        DBUtil::closeConnection($con);
        */



        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        //Mage::app()->setCurrentStore($this->store_id);
        $product  = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->sku);


        if ($product) {
            //$subinventory = $item->subinventory;
            //$product->setData('subinventory',$subinventory);
            //$product->save();



            //$id = $product->getIdBySku(trim($sku));
            $id = $product->getId();

            // $product->setId($id);
            $qty = $item->onHand;
            $qty_processing = $this->getQTAProcessingOrdersForQOH($item);
            $qty = $qty - $qty_processing;
            if ($qty<0) $qty = 0;
            //$this->log->LogDebug("QOH: ".$item->sku." , (processing: ".$qty_processing."), new QTA: ".$qty);

            // $this->log->LogDebug("QOH: $id ($item->sku) , qta($qty)");
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

            //$stockItemData = $stock->getData();  // /* RINO 08/08/2016
            //if (empty($stockItemData)) {
            if ($stock->getId() == null) {


                $stock->setData('manage_stock', 1);
                $stock->setData('is_in_stock', $qty ? 1 : 0);
                $stock->setData('use_config_manage_stock', 0);
                $stock->setData('stock_id', 1);
                $stock->setData('product_id', $id);

                $newQty = $qty;
                $stock->setQty($newQty);
                $stock->setIs_in_stock(($qty > 0) ? 1 : 0);


                /* ------------ */
                $manage_stock=1;
                $is_in_stock = $qty ? 1 : 0;
                $use_config_manage_stock = 0;
                $stock_id = 1;
                $con = OMDBManager::getMagentoConnection();
                $sql ="INSERT INTO cataloginventory_stock_item ( manage_stock,  is_in_stock,  use_config_manage_stock,  stock_id,  product_id, qty )
                                                        VALUES ($manage_stock, $is_in_stock, $use_config_manage_stock, $stock_id, $id,        $qty );";  //RINO 04/10/2016
                //$sql ="INSERT INTO cataloginventory_stock_item (product_id,qty,stock_id) VALUES ('$id','$qty',1);";
                //echo "\nSQL: ".$sql;
                $res = mysql_query($sql);
                /* ------------- */



                //$stock->save();




                $this->log->LogDebug("Creato stock_item mancante per product id: $id ($item->sku) ");

                //$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product->getId());
            }  else {

                //$stockQty = $stock->getQty();
                //$newQty = $stockQty + $qty; //aggiorna con il delta caricato dal file

                $newQty = $qty;
                $stock->setQty($newQty);
                $stock->setIs_in_stock(($qty > 0) ? 1 : 0);

                /* ------------ */
                $is_in_stock = $qty ? 1 : 0;
                $con = OMDBManager::getMagentoConnection();  //RINO 09/08/2016
                $sql ="UPDATE cataloginventory_stock_item set qty=$qty, is_in_stock=$is_in_stock where product_id='$id'";  //RINO 04/10/2016
                //echo "\nSQL: ".$sql;
                $res = mysql_query($sql);
                /* ------------- */
                //$stock->save();
                $this->log->LogDebug("Aggiornato stock_item per product id: $id ($item->sku) qta: $newQty");
            }

            //$product->save(); // RINO 08/08/2016



        }

    }

    public function setROH(ItemObject $item) {

        //AGggiorna descrizione (nota che tabella per le descerizione è catalog_product_entity_text)



        /*
          $con = DBUtil::getMagentoConnection();

        $sql="UPDATE catalog_product_entity_varchar v
		INNER JOIN catalog_product_entity c
		ON c.entity_id=v.entity_id
		SET v.value='$data_disp'
		WHERE v.attribute_id=280 AND c.sku='$t_sku'";
        $res = mysql_query($sql);
        DBUtil::closeConnection($con);
        */


        /*Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $product  = Mage::getModel('catalog/product')->loadByAttribute('sku', $item->sku);


        if ($product) {
            //$id = $product->getIdBySku(trim($sku));
            $id = $product->getId();

            // $product->setId($id);
            $qty = $item->quantity_delta;
            $this->log->LogDebug("ROH: $id ($item->sku) , qta($qty)");
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);


            $stockQty = $stock->getQty();
            $newQty = $stockQty + $qty; //aggiorna con il delta caricato dal file

            $stock->setQty($newQty);
            $stock->setIs_in_stock(($qty > 0) ? 1 : 0);
            $stock->save();
        }*/

    }

}

