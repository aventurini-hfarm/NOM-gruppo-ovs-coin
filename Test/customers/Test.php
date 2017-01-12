<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 04/08/15
 * Time: 11:04
 */

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class Test {

    public function getCustomerByDWId($codice) {
        $customers = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('customer_id')
            ->addAttributeToFilter('customer_no',$codice)->load();
        foreach ($customers as $customer) {
            print_r($customer->getData());
        }
    }

    public function loadCliente($id) {
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->load($id);

        print_r($customer->getData());
        $customer->email="test_silvia.sparago@inwind.it";
        $customer->save();

    }
}


$t = new Test();
$t->getCustomerByDWId('00081033');
