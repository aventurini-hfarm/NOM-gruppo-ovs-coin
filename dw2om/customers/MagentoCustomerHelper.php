<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 20:16
 */

require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class MagentoCustomerHelper {
    private $log;

    public function __construct(){

        $this->log = new KLogger('/var/log/nom/magento_customer_helper.log',KLogger::DEBUG);
    }

    public function import(CustomerObject $obj){

        if ( ($customer = $this->checkIfUserExists($obj->email, $obj->customer_no))){
            //fai update
            $this->updateMagentoUser($obj, $customer);
        }  else {
            //crea nuovo
            $id = $this->createMagentoUser($obj);
            $this->log->LogDebug("Created Magento User: ".$id);
        }
    }

    public function getCustomerByDWId($codice) {
        $customers = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('customer_id')
            ->addAttributeToFilter('customer_no',$codice)->load();

        $customer_id = null;
        foreach ($customers as $customer) {
            $customer_id = $customer->entity_id;

        }

        return $customer_id;
    }

    private function checkIfUserExists($email, $dw_customer_no) {


/*
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->loadByEmail($email);
        // FARE CHECK SU DW USER ID e non su EMAIL

        if ($customer->getId()) {
            $id = $customer->getId();
            //echo "\nFound Customer: $id ($email)";
        }
*/
        $this->log->LogDebug("Cerca cliente: ".$dw_customer_no);
        $customer_id = $this->getCustomerByDWId($dw_customer_no);
        $customer = null;

        if ($customer_id) {
            $this->log->LogDebug("Cliente trovato: ".$dw_customer_no." , ".$customer_id);
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->load($customer_id);
        } else $customer = null;


        return $customer;
    }

    private function createMagentoUser(CustomerObject $obj) {


        $customer = Mage::getModel("customer/customer");

        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->setStore(Mage::app()->getStore());
        //$customer->setStore($obj->store_id);
        //print_r($record);

        $obj_field = get_object_vars($obj);
        $ini_array = parse_ini_file(realpath(dirname(__FILE__))."/../../config/customer_mapping.ini");
        foreach ($obj_field as $key=>$value) {
            $magento_field_name = $ini_array[$key];
            if ($magento_field_name) {
                $this->log->LogDebug("Setting: $magento_field_name, $value");
                $customer->setData($magento_field_name, $value);
            } else {
                $this->log->LogDebug("Skipping: DW field $key:$value");
            }
        }



        try {

            $customer->save();

        } catch (Exception $e) {

            $this->log->LogError("Errore Creazione Utente: ".$e->getMessage());
            return null;
        }

        $id = $customer->getId();
        $this->log->LogDebug ("Customer ID: ".$id);
        return $id;

    }

    public function updateMagentoUser(CustomerObject $obj, $customer) {


        //$customer = Mage::getModel("customer/customer");

        //$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        //$customer->setStore(Mage::app()->getStore());
        //print_r($record);

        $obj_field = get_object_vars($obj);
        $ini_array = parse_ini_file(realpath(dirname(__FILE__))."/../../config/customer_mapping.ini");
        foreach ($obj_field as $key=>$value) {
            $magento_field_name = $ini_array[$key];
            if ($magento_field_name) {
                $this->log->LogDebug("Setting: $magento_field_name, $value");
                //if ($magento_field_name=='email') {continue; } // DA RIMUOVERE
                $customer->setData($magento_field_name, $value);
            } else {
                $this->log->LogDebug("Skipping: DW field $key:$value");
            }
        }



        try {

            $customer->save();

        } catch (Exception $e) {

            $this->log->LogError("Errore Update Utente: ".$e->getMessage());
            return null;
        }


    }
} 