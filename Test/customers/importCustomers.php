<?php
/**
 * Created by PhpStorm.
 * User: Rino
 * Date: 15/06/16
 * Time: 20:23
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();


class CustomerObject {

}

class ImportCustomers
{

    private $config;
    private $log;

    public function __construct()
    {
        $this->log = new KLogger('import_customers.log', KLogger::DEBUG);
    }

    function createMagentoUser(CustomerObject $obj)
    {




        $customers = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('customer_no')
            ->addAttributeToSelect('sg_user_id')
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addExpressionAttributeToSelect('email','LOWER(email)',array('email'))
            ->addAttributeToFilter('email',strtolower($obj->email))
            ->load();
        //->addExpressionAttributeToSelect('fullname', 'CONCAT({{firstname}}, " ", {{lastname}})', array('firstname','lastname'));



        if ($customers->getSize()>0) {
            $customer=$customers->getFirstItem();
            //print_r($customer->getData());
            echo "\n" . $customer->getData("customer_no") . " -\t\t\t\t " .
                $customer->getData("sg_user_id") . " -\t\t\t\t " .
                $customer->getFirstname() . " -\t\t\t\t " .
                $customer->getLastname() . " -\t\t\t\t" .
                $customer->getData("email") ." | " .
                $obj->email;

            $customer
                //->setWebsiteId(Mage::app()->getWebsite()->getId())
                //->setStore(Mage::app()->getStore())
                ->setData("sg_user_id", $obj->customer_id)   // questo è l'id customer proveniente da stargate
                ->setData("customer_no",$obj->customer_no)   // questo è l'id customer di demandware (web_user_id) deve essere della forma
                ->setFirstname($obj->first_name)
                ->setLastname($obj->last_name);

            try {

                $customer->save();

            } catch (Exception $e) {

                echo $e->getMessage();
                $this->log->LogError("Errore Update Utente: ".$obj->email." " . $e->getMessage());
                return null;
            }
        } else {
            echo "\n nuovo utente ". $obj->email;
            $customer = Mage::getModel("customer/customer")
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->setStore(Mage::app()->getStore())
                ->setData("sg_user_id", $obj->customer_id)   // questo è l'id customer proveniente da stargate
                ->setData("customer_no",$obj->customer_no)   // questo è l'id customer di demandware (web_user_id) deve essere della forma
                ->setFirstname($obj->first_name)
                ->setLastname($obj->last_name)
                ->setEmail($obj->email);
            try {

                $customer->save();

            } catch (Exception $e) {

                $this->log->LogError("Errore Creazione Utente: ".$obj->email." " . $e->getMessage());
                return null;
            }
        }
        



        //$id = $customer->getId();
        //$this->log->LogDebug("Customer ID: " . $id);
        //return $id;

    }




    public function process()
    {

        $customers = array();
        $customers_count = 0;

        if (($handle = fopen($this->file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {

                if ($customers_count==0) {
                    // tracciato campi cvs
                } else {
                    array_push($customers, $data);
                }
                $customers_count++;

            }

            fclose($handle);
        }

        if ($customers_count>0) {
            // add customers
            $customer = new CustomerObject();
            foreach ($customers as $c) {
                $customer->customer_id = ltrim($c[0],'0');  //RINO 21/07/2016 il magazzino si aspetta nella delivery sg_user_id ltrimmed di zeri
                $customer->customer_no = $c[1];  //RINO 13/08/2016 il magazzino accetta customer_no cia paddati di zero a sinistra che non.
                $customer->first_name = $c[2];
                $customer->last_name = $c[3];
                $customer->email = $c[4];
                $this->createMagentoUser($customer);
            }
        }


    }

}

$im = new ImportCustomers();
$im->file="/home/OrderManagement/Test/customers/ovs_customers_con_country.csv";

/*
$customer=new CustomerObject();
$customer->customer_no="8008";
$customer->first_name="rino";
$customer->last_name="billa";
$customer->email="rinobilla@tiscalinet.it";
$im->createMagentoUser($customer);
*/

$im->process();




