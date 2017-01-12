<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/05/15
 * Time: 08:51
 */
require_once realpath(dirname(__FILE__)) . "/../common/OMDBManager.php";
require_once realpath(dirname(__FILE__)) . "/../wh2om/shipment/ShipmentObject.php";
require_once realpath(dirname(__FILE__))."/OMDBConstant.php";


class DeliveryDBHelper {

    public function addDelivery($delivery_id, $order_number, $subinventory) {

        $con = OMDBManager::getConnection();
        //$status = ORDER_DELIVERY_STATUS_NOT_VALID;
        $status = 0;


        //metto la precedente delivery se esiste con stato non valido
        $sql = "UPDATE delivery SET status = $status WHERE order_number = '$order_number'";
        $res = mysql_query($sql);

        //inseriesco la nuova delivery
        //$status = ORDER_DELIVERY_STATUS_VALID;
        $status = 1;
        $sql ="INSERT INTO delivery (delivery_id, order_number, subinventory, status)
        VALUES ('$delivery_id', '$order_number', '$subinventory', $status)";
        $res = mysql_query($sql);
        OMDBManager::closeConnection($con);
    }

    public static function getDWOrderNumberByDeliveryId($delivery_id) {
        $con = OMDBManager::getConnection();
        $sql ="SELECT * FROM delivery WHERE delivery_id='$delivery_id'";
        $res = mysql_query($sql);
        $order_no = null;
        while ($row = mysql_fetch_object($res)) {
            $order_no = $row->order_number;
        }
        OMDBManager::closeConnection($con);
        return $order_no;
    }

    /*
    public function updateDeliveryStatus() {
        $con = OMDBManager::getConnection();
        $status = ORDER_DELIVERY_STATUS_VALID;
        $esito = $shipment->esito;
        $delivery_id = $shipment->delivery_id;
        $sql = "UPDATE delivery SET status = $status , esito=$esito WHERE delivery_id = $delivery_id ";
        $res = mysql_query($sql);
        OMDBManager::closeConnection($con);
    }
    */


    /*
        public function updateDeliveryWithShipment(ShippingObject $shipment) {
            $con = OMDBManager::getConnection();
            $status = ORDER_DELIVERY_STATUS_VALID;
            $esito = $shipment->esito;
            $delivery_id = $shipment->delivery_id;
            $sql = "UPDATE delivery SET status = $status , esito=$esito WHERE delivery_id = $delivery_id ";
            $res = mysql_query($sql);
            OMDBManager::closeConnection($con);
        }
    */



} 