<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 25/04/15
 * Time: 13:58
 */

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class TestInvoice {

    public function test($increment_id){
        // $order=Mage::getModel('sales/order')->load($orderId);

        $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);


            $items = array();
            foreach ($order->getAllItems() as $item) {
                $items[$item->getId()] = $item->getQtyOrdered();
            }
//public function create($orderIncrementId, $itemsQty, $comment = null, $email = false, $includeComment = false)
            $invoiceId=Mage::getModel('sales/order_invoice_api')->create($order->getIncrementId(),$items,null,false,true);
            Mage::getModel('sales/order_invoice_api')->capture($invoiceId);

    }


    public function test2($increment_id) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
        echo "\nGetting InvoiceID";
        $invoiceId = Mage::getModel('sales/order_invoice_api')
            ->create($order->getIncrementId(), array());

        print_r($invoiceId);

        $invoice = Mage::getModel('sales/order_invoice')
            ->loadByIncrementId($invoiceId);

        print_r($invoice);

        /**
         * Pay invoice
         * i.e. the invoice state is now changed to 'Paid'
         */
        $invoice->capture()->save();

    }
}

$t = new TestInvoice();
$t->test('100000251');