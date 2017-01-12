<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 20:16
 */
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
require_once realpath(dirname(__FILE__))."/../../common/OMDBManager.php";
Mage::app();

class MagentoProductHelper {
    private $log;

    public function __construct()
    {

        $this->log = new KLogger('/var/log/nom/import_catalog.log',KLogger::DEBUG);
    }

    public function import(ProductObject $obj){

        if ( ($pId = $this->checkIfProductExists($obj->sku))){
            //fai update
            $product = Mage::getModel('catalog/product')->load($pId);
            $this->updateMagentoProduct($obj, $product);
        }  else {
            //crea nuovo
            $id = $this->createMagentoProduct($obj);
            $this->log->LogDebug("Created Magento Product: ".$id);
        }
    }

    private function checkIfProductExists($sku) {



        $id = Mage::getModel('catalog/product')->getIdBySku($sku);

        return $id;
    }

    private function createMagentoProduct(ProductObject $obj) {


        /*RINO 09/09/2016  sostituito con accesso diretto al db magento perche molto più veloce
        $product = Mage::getModel('catalog/product');

        $product->setName($obj->baseProductName);
        $product->setDescription($obj->longDesc);
        $product->setData('baseproductcode', $obj->baseProductCode);
        $product->setSku($obj->sku);
        $product->setTypeId('simple');
        $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        $product->setStatus(1);//1=Enabled; 2=Disabled;
        $product->setData('prenotabile', $obj->prenotabile);
        // set visibility
        $product->setVisibility(4);

        // get and set default attribute set
        $def_attribute_set = Mage::getModel('catalog/product')->getResource()->getEntityType()->getDefaultAttributeSetId();
        $product->setAttributeSetId($def_attribute_set); // need to look this up - See more at:

        $stock_data=array(
            'use_config_manage_stock' => 1,
            'qty' => 0,
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
        $product->setData('stock_data',$stock_data); //Da commentare in caso di update


        try {

            $product->save();

        } catch (Exception $e) {

            $this->log->LogError("\nErrore: $e");
            die();
            print_r($ob);
            return null;
        }

         $id = $product->getId();
        $this->log->LogDebug("New product ID: ".$id);
        return $id;

        */


        //RINO 09/09/2016  Accesso diretto al db magento
        $con = OMDBManager::getMagentoConnection();
        $entity_type_id = 4;    // catalog/product
        $attribute_set_id = 4;  //default
        $type_id = 'simple';
        $sql ="INSERT INTO catalog_product_entity (entity_type_id, attribute_set_id, sku,type_id, created_at, updated_at) VALUES ($entity_type_id,$attribute_set_id,'$obj->sku','$type_id', now(),now());";
        //echo "\nSQL: ".$sql;
        $res = mysql_query($sql);

        $entity_id=mysql_insert_id();

        // inserisce gli attributi varchar
        $entity_type_id= 4;
        $name_cod=71; $name_value=$obj->baseProductName;
        $url_key_cod=97; $url_key_value=$obj->baseProductName;
        $store_id=0;
        $sql ="INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES ($entity_type_id, $name_cod, $store_id, $entity_id, '$name_value');";
        $res = mysql_query($sql);
        $sql ="INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES ($entity_type_id, $url_key_cod, $store_id, $entity_id, '$url_key_value');";
        $res = mysql_query($sql);


        // inserisce gli attributi text
        $entity_type_id= 4;
        $description_cod=72; $description_value=$obj->longDesc;
        $short_description_cod=73; $short_description_value=$obj->baseProductName;
        $store_id=0;
        $sql ="INSERT INTO catalog_product_entity_text (entity_type_id, attribute_id, store_id, entity_id, value) VALUES ($entity_type_id, $description_cod, $store_id, $entity_id, '$description_value');";
        $res = mysql_query($sql);
        $sql ="INSERT INTO catalog_product_entity_text (entity_type_id, attribute_id, store_id, entity_id, value) VALUES ($entity_type_id, $short_description_cod, $store_id, $entity_id, '$short_description_value');";
        $res = mysql_query($sql);

        // inserisce gli attributi int
        $entity_type_id= 4;
        $status=96; $status_value=1;
        $visibility=102; $visibility_value = 4;
        $store_id=0;
        $sql ="INSERT INTO catalog_product_entity_int (entity_type_id, attribute_id, store_id, entity_id, value) VALUES ($entity_type_id, $status, $store_id, $entity_id, $status_value);";
        $res = mysql_query($sql);
        $sql ="INSERT INTO catalog_product_entity_int (entity_type_id, attribute_id, store_id, entity_id, value) VALUES ($entity_type_id, $visibility, $store_id, $entity_id, $visibility_value);";
        $res = mysql_query($sql);

        //tls  rino rino 22/09/2016
        $con_support = OMDBManager::getConnection();
        $obj->baseProductNameEN;
        $baseNameEN=$obj->baseProductNameEN; $longDescEN=$obj->longDescEN;
        $baseNameES=$obj->baseProductNameES; $longDescES=$obj->longDescES;
        $baseNameIT=$obj->baseProductNameIT; $longDescIT=$obj->longDescIT;
        $sql ="INSERT INTO estero_catalog (entity_id, baseNameEN, longDescEN, baseNameES, longDescES, baseNameIT, longDescIT) ".
            "VALUES ($entity_id, '$baseNameEN', '$longDescEN', '$baseNameES', '$longDescES', '$baseNameIT', '$longDescIT') ON DUPLICATE KEY UPDATE ".
            "baseNameEN = VALUES(baseNameEN), longDescEN = VALUES(longDescEN), ".
            "baseNameES = VALUES(baseNameES), longDescES = VALUES(longDescES), ".
            "baseNameIT = VALUES(baseNameIT), longDescIT = VALUES(longDescIT)";
        $res = mysql_query($sql,$con_support);
        $modificato=mysql_affected_rows();
        // end tls

        $id = $entity_id;
        $this->log->LogDebug("New product ID: ".$id);
        return $id;

    }

    private function updateMagentoProduct(ProductObject $obj, $product) {


        //$customer = Mage::getModel("customer/customer");

        //$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        //$customer->setStore(Mage::app()->getStore());
        //print_r($record);

        /*   RINO 09/09/2016  sostituito con accesso diretto al db magento perche molto più veloce
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $product->setName($obj->baseProductName);
        $product->setDescription($obj->longDesc);
        $product->setData('baseproductcode', $obj->baseProductCode);
        $product->setData('prenotabile', $obj->prenotabile);


        try {

            $product->save();

        } catch (Exception $e) {

            $this->log->LogError("Errore: $e");
            die();
            print_r($ob);

            return null;
        }*/

        //RINO 09/09/2016  Accesso diretto al db magento
        $con = OMDBManager::getMagentoConnection();

        $entity_id=$product->getEntityId();

        $name_cod=71; $name_value=$obj->baseProductName;
        $sql ="UPDATE catalog_product_entity_varchar SET value = '$name_value' WHERE entity_id = $entity_id and attribute_id=$name_cod";
        $res = mysql_query($sql);
        $modificato=mysql_affected_rows();


        $description_cod=72; $description_value=$obj->longDesc;
        $sql ="UPDATE catalog_product_entity_text SET value = '$description_value' WHERE entity_id = $entity_id and attribute_id=$description_cod";
        $res = mysql_query($sql);
        $modificato=mysql_affected_rows();

        //tls  rino 22/09/2016
        $con_support = OMDBManager::getConnection();
        $obj->baseProductNameEN;
        $baseNameEN=$obj->baseProductNameEN; $longDescEN=$obj->longDescEN;
        $baseNameES=$obj->baseProductNameES; $longDescES=$obj->longDescES;
        $baseNameIT=$obj->baseProductNameIT; $longDescIT=$obj->longDescIT;
        $sql ="INSERT INTO estero_catalog (entity_id, baseNameEN, longDescEN, baseNameES, longDescES, baseNameIT, longDescIT) ".
            "VALUES ($entity_id, '$baseNameEN', '$longDescEN', '$baseNameES', '$longDescES', '$baseNameIT', '$longDescIT') ON DUPLICATE KEY UPDATE ".
            "baseNameEN = VALUES(baseNameEN), longDescEN = VALUES(longDescEN), ".
            "baseNameES = VALUES(baseNameES), longDescES = VALUES(longDescES), ".
            "baseNameIT = VALUES(baseNameIT), longDescIT = VALUES(longDescIT)";
        $res = mysql_query($sql,$con_support);
        $modificato=mysql_affected_rows();
        // end tls


        $id = $product->getId();
        if ($modificato>0)
            $this->log->LogDebug("Updated product ID: ".$id);
        else
            $this->log->LogDebug("Skipped product ID: ".$id);
        return $id;


    }
} 