<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 11/06/15
 * Time: 11:12
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

require_once ('/home/OrderManagement/dw2om/orders/MagentoOrderHelper.php');

class MagentoOrderHelperTest {

    public function testOrderHistory() {
        echo "\nGeneraizone Lista";
        $helper = new MagentoOrderHelper();
        $lista = $helper->getOrderHistoryByCustomerId('00008070','4563');
        print_r($lista);
        echo "\nEND";
    }

    public function testOrderStatus() {
        echo "\nCambio stato";
        $helper = new MagentoOrderHelper();
        $helper->setStatusComplete("100000238");
        echo "\nCambio stato OK";

    }

    public function testCreateFiscalInfo() {
        $helper = new MagentoOrderHelper();
        $helper->createFiscalInfo("00148958");
    }

    public function testPrepareConfirmOrder() {
        $helper = new MagentoOrderHelper();
        $helper->prepareConfirmOrder("00148958");

    }

}

$t = new MagentoOrderHelperTest();
//$t->testCreateFiscalInfo();
//$t->testPrepareConfirmOrder();
//$t->testOrderHistory();
$t->testOrderStatus();