<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 08/08/15
 * Time: 12:44
 */

require_once realpath(dirname(__FILE__))."/../../common/OMDBManager.php";

class BillHelper {

    public static function getDWOrderNumberByBillNumber($bill_number) {
        $con = OMDBManager::getMagentoConnection();
        $sql ="SELECT dw_order_number FROM sales_flat_order WHERE bill_number='$bill_number'";
        $res = mysql_query($sql);
        $order_number = null;
        while ($row = mysql_fetch_object($res)) {
            $order_number = $row->dw_order_number;
        }

        OMDBManager::closeConnection($con);
        return $order_number;
    }

    public static function getDWOrderByBillNumberFromCreditMemo($bill_number) {
        $con = OMDBManager::getMagentoConnection();
        $sql ="SELECT order_id,dw_order_number,sales_flat_creditmemo.entity_id FROM sales_flat_creditmemo, sales_flat_order WHERE sales_flat_creditmemo.order_id= sales_flat_order.entity_id and sales_flat_creditmemo.bill_number='$bill_number'";
        $res = mysql_query($sql);
        $order_number = null;



        while ($row = mysql_fetch_object($res)) {
            $obj = new stdClass();
            $obj->creditmemo_id = $row->entity_id;
            $obj->order_id = $row->order_id;
            $obj->dw_order_number = $row->dw_order_number;
        }

        OMDBManager::closeConnection($con);
        return $obj;
    }
} 