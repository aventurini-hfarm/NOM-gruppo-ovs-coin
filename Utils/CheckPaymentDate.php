<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 25/10/16
 * Time: 20:28
 */
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

require_once realpath(dirname(__FILE__))."/../common/OMDBManager.php";

class CheckPaymentDate {



    public function process() {
        $lista = $this->getListaFromPayment();
        $this->checkOrder($lista);
    }

    public function checkOrder($lista) {
       $con = OMDBManager::getMagentoConnection();
        $contatore=0;
        foreach ($lista as $item) {
            //print_r($item);
            $contatore++;
            $dw_order_number = $item->order_number;
            $sql ="SELECT bill_date , bill_number FROM sales_flat_order WHERE dw_order_number = '$dw_order_number'";
            //echo "\nsql: ".$sql;
            $res = mysql_query($sql);
            $row = mysql_fetch_object($res);
            //echo "\nContatore: ".$contatore;
            if (!$row->bill_number) continue;
            //echo "\nContatore: ".$contatore;
            $data_payment = date('d/m/Y', strtotime($item->trx_timestamp));
            //echo "\nOrdine: ".$dw_order_number.", bill_date: ".$row->bill_date.", paydate: ".$data_payment.", bill_number: ".$row->bill_number;
            if ($row->bill_date!=$data_payment) echo "\nErrore, ordine: ".$dw_order_number.", bill_date: ".$row->bill_date.", paydate: ".$data_payment.", bill_number: ".$row->bill_number;

        }
    }
    public function getListaFromPayment()
    {
        $con = OMDBManager::getConnection();
        $sql ="SELECT order_number, trx_timestamp FROM `payments` WHERE (trx_timestamp between '2016-10-20 00:00:00' AND '2016-11-05 23:59:59')";
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            //if ($row->order_number!='00288520') continue;
            $lista[] = $row;
        }

        OMDBManager::closeConnection($con);
        //print_r($lista);
        return $lista;
    }

}



//$lista = array(array('00294061 ','00037291'));

$t = new CheckPaymentDate();
$t->process();
//$t->processFixPadding();
