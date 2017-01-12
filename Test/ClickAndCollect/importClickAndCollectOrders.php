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

require_once('/home/OrderManagement/webservices/orderhistory/ClickCollectHelper.php');


Mage::app();


class ImportClickAndCollectOrderStatus
{

    private $config;
    private $log;

    public function __construct()
    {
        $this->log = new KLogger('import_customers.log', KLogger::DEBUG);
    }

    function updateOrderStatus(order $obj) {

        $order_number = $obj->number;   // RINO 20/07/2016 $order_number==dw_order_number
        $status = $obj->status_code;

        $helper = new ClickCollectHelper();
        if ($status=='IN_STORE') {
            //$helper->updateOrderStatus($order_number,$status);
            $helper->addCustomAttribute($order_number, "", "STATUS", $status);
            $helper->updateMagentoStoreOrderStatus($order_number, $status);

        }
        if ($status=='DELIVERED_TO_CUST') {
            //$helper->updateOrderStatus($order_number,$status);  //  RINO 18/07/2016  TODO  cambiare lo stato in DELIVERED
            $helper->addCustomAttribute($order_number, "", "STATUS", "DELIVERED");
            $helper->updateMagentoStoreOrderStatus($order_number, $status);
        }

    }



    public function process()
    {

        $orders = array();
        $orders_count = 0;

        if (($handle = fopen($this->file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {

                if ($orders_count==0) {
                    // tracciato campi cvs
                } else {
                    array_push($orders, $data);
                }
                $orders_count++;

            }

            fclose($handle);
        }

        if ($orders_count>0) {
            // add customers
            $order = new stdClass();
            foreach ($orders as $c) {
                $order->status_code = $c[0];
                $order->number = $c[1];
               
                $this->updateOrderStatus($order);
            }
        }


    }

}

$im = new ImportClickAndCollectOrderStatus();
$im->file="/home/OrderManagement/Test/ClickAndCollect/orders.csv";


$im->process();




