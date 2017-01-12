<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/08/15
 * Time: 13:05
 */

require_once "/home/OrderManagement/common/OMDBManager.php";
require_once "/home/OrderManagement/common/KLogger.php";


class ClickCollectHelper {

    private $log;

    public function __construct() {
        $this->log = new KLogger('/var/log/nom/delivery_service_helper.log',KLogger::DEBUG);
    }

    public function addCustomAttribute($order_id, $clerk, $chiave, $valore) {
        $con = OMDBManager::getConnection();

        //cancella vecchia
        $sql ="DELETE FROM click_collect WHERE order_id='$order_id' AND chiave='$chiave' AND valore='$valore'";
       // $this->log->LogDebug("Resulting sql click & Collect helper: ".$sql);
        $res = mysql_query($sql);

        $data_operazione = date('Y-m-d H:i:s');
         $sql="INSERT INTO click_collect (order_id, data_operazione, clerk, chiave, valore)
        VALUES ('$order_id', '$data_operazione', '$clerk', '$chiave', '$valore')";
            // echo "\nSQL: ".$sql;
        //$this->log->LogDebug("Resulting sql click & Collect helper: ".$sql);
            $res = mysql_query($sql);

        OMDBManager::closeConnection($con);
    }


    public function updateMagentoStoreOrderStatus($dw_order_no, $status) {
        $con = OMDBManager::getMagentoConnection();
        $sql = "UPDATE sales_flat_order SET store_order_status='$status' WHERE dw_order_number='$dw_order_no'";
        $res = mysql_query($sql);
        OMDBManager::closeConnection($con);
    }

    public function updateOrderStatus($dw_order_no, $status) {   //RINO 20/07/2016
        $con = OMDBManager::getConnection();
        $sql = "UPDATE click_collect SET valore='$status' WHERE order_id='$dw_order_no' AND chiave='STATUS'";

        $res = mysql_query($sql);
        OMDBManager::closeConnection($con);
    }

    public function getCustomAttribute($order_id, $chiave, $valore) {
        $con = OMDBManager::getConnection();
        $sql = "SELECT * FROM click_collect WHERE order_id='$order_id' AND chiave='$chiave' AND valore='$valore'";
        $res = mysql_query($sql);
        //echo "\nSQL: ".$sql;
        $obj = new stdClass();
        while ($row = mysql_fetch_object($res)) {
            $obj->data_operazione = $row->data_operazione;
            $obj->clerk = $row->clerk;
        }
        OMDBManager::closeConnection($con);
        return $obj;

    }

    public function getCustomAttributeValue($order_id, $chiave) {
        $con = OMDBManager::getConnection();
        $sql = "SELECT * FROM click_collect WHERE order_id='$order_id' AND chiave='$chiave'";
        $res = mysql_query($sql);
        $obj = new stdClass();
        while ($row = mysql_fetch_object($res)) {
            $obj->data_operazione = $row->data_operazione;
            $obj->clerk = $row->clerk;
            $obj->valore = $row->valore;
        }
        OMDBManager::closeConnection($con);
        return $obj;

    }
}


