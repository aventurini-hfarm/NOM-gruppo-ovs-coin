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

class FixInventoryStock {

    private $log;
    private $con;

    public function __construct($store_id)
    {
        $this->log = new KLogger('/var/log/nom/magento_fix_inventory_helper.log',KLogger::DEBUG);
        $this->store_id = $store_id;
        $this->con = OMDBManager::getMagentoConnection();

    }

    public function process() {
        if (($handle = fopen("./catalog_product_entity.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $entity_id= $data[0];
                echo "\nProcessing: ".$entity_id;
                $this->fixStock($entity_id);
                }
            }
            fclose($handle);
    }

    public function processSQL(){
        $w = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "SELECT entity_id
                  FROM   catalog_product_entity
                  LEFT OUTER JOIN cataloginventory_stock_item
                  ON (catalog_product_entity.entity_id = cataloginventory_stock_item.product_id)
                  WHERE cataloginventory_stock_item.product_id IS NULL";
        $this->log->LogDebug("sql: ".$sql);
        $res = $w->query($sql);

        $entity_list = array();
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $entity_list[] = $row["entity_id"];
        }

        $this->log->LogDebug("Found: ".sizeof($entity_list));

        foreach ($entity_list as $entity_id) {
            $this->fixStock($entity_id);
        }

    }


    public function fixStock($entity_id) {


            $product  = Mage::getModel('catalog/product')->load($entity_id);


            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            if ($stock->getId() == null) {


                /* ------------ */
                $manage_stock=1;
                $is_in_stock = 0;
                $use_config_manage_stock = 0;
                $stock_id = 1;
                $id = $entity_id;
                $qty = 0;

                $sql ="INSERT INTO cataloginventory_stock_item ( manage_stock,  is_in_stock,  use_config_manage_stock,  stock_id,  product_id, qty )
                                                        VALUES ($manage_stock, $is_in_stock, $use_config_manage_stock, $stock_id, $id,        $qty );";  //RINO 04/10/2016
                $res = mysql_query($sql);
                if (!$res) {
                    echo "\nErorre SQL ".$sql;
                    die();
                }
                echo "\nCreato stock_item sql: ".$sql;
                /* ------------- */
            }  else {
                echo "\nStock item esiste: ".$entity_id;

            }

            //$product->save(); // RINO 08/08/2016

    }

    public function closeProcessing() {
        OMDBManager::closeConnection($this->con);
    }

}

$t = new FixInventoryStock(1);
$t->processSQL();
