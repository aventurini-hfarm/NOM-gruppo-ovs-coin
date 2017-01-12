<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 27/07/15
 * Time: 12:07
 */

require_once realpath(dirname(__FILE__))."/../../dw2om/orders/MagentoOrderHelper.php";

class TestBaseGrandTotal {

    public function run($order_number) {
    $magOrderHelper = new MagentoOrderHelper();
        $increment_id = $magOrderHelper->getOrderIdByDWId($order_number);


            //devo prendere il valore dall'ordine perchè in caso di reso è diverso il valore dal paymentinf
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $orderValue = $order->getBaseGrandTotal(); //TODO VERIFICARE che effettivamente sia il valore corretto


        echo "Payment Amount: $orderValue (".$order_number.")";
    }

}

$t = new TestBaseGrandTotal();
$t->run('00155560');