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


class DeliveryExportDBHelper {

    public function addDelivery($deliveryObj) {

        $con = OMDBManager::getConnection();
        //$status = ORDER_DELIVERY_STATUS_NOT_VALID;
        $status = 0;
        $order_number = $deliveryObj->order_number;
        $subinventory = $deliveryObj->subinventory;
        $delivery_id = $deliveryObj->delivery_id;
        $valore = $deliveryObj->totale_ordine_ripartito;
        $totale_merce = $deliveryObj->totale_righe;
        $spese_spedizione = $deliveryObj->spese_spedizione_ripartite;
        $sconto = $deliveryObj->valore_sconto_ripartito;

        //metto la precedente delivery se esiste con stato non valido
        $sql = "DELETE FROM delivery_export WHERE dw_order_number = '$order_number' AND subinventory='$subinventory'";
        //echo "\nSQL: ".$sql;
        $res = mysql_query($sql);
        $current_data = date('Y-m-d H:m:s');
        //inseriesco la nuova delivery
        //$status = ORDER_DELIVERY_STATUS_VALID;
        $status = 0;
        $sql ="INSERT INTO delivery_export (dw_order_number, delivery_id,  subinventory, data, status, valore, totale_merce, spese_spedizione, sconto)
        VALUES ('$order_number', '$delivery_id','$subinventory', '$current_data', $status, $valore, $totale_merce, $spese_spedizione, $sconto)";
        $res = mysql_query($sql);
        echo "\nSQL:".$sql;
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

    public function getDeliveryInfo($delivery_id, $dw_order_number){
        $con = OMDBManager::getConnection();
        $tmp_order_number = ltrim($dw_order_number, '0');
        $sql ="SELECT * FROM delivery_export WHERE (dw_order_number='$dw_order_number' OR  dw_order_number='$tmp_order_number') AND delivery_id=$delivery_id";
        $res = mysql_query($sql);
        $row = mysql_fetch_object($res);
        OMDBManager::closeConnection($con);
        return $row;
    }


    public function getNumberOfDeliveries($dw_order_number) {
        $con = OMDBManager::getConnection();
        $tmp_order_number = ltrim($dw_order_number, '0');
        $sql ="SELECT count(*) as numero_delivery FROM delivery_export WHERE (dw_order_number='$dw_order_number' OR  dw_order_number='$tmp_order_number')";
        $res = mysql_query($sql);
        $row = mysql_fetch_object($res);
        OMDBManager::closeConnection($con);
        return $row->numero_delivery;

    }



    public function getDeliveriesDaNonInviare($dw_order_number) {
        $con = OMDBManager::getConnection();
        $tmp_order_number = ltrim($dw_order_number, '0');
        $sql ="SELECT subinventory FROM delivery_export WHERE (dw_order_number='$dw_order_number' OR  dw_order_number='$tmp_order_number') AND (status=1 OR status=0)";
        //echo "\nSQL: ".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row= mysql_fetch_object($res)) {
            $lista[] = $row->subinventory;
        }
        OMDBManager::closeConnection($con);
        return $lista;

    }

    public function getDeliveriesByStatus($dw_order_number, $status) {
        $con = OMDBManager::getConnection();
        $tmp_order_number = ltrim($dw_order_number, '0');
        $sql ="SELECT subinventory FROM delivery_export WHERE (dw_order_number='$dw_order_number' OR  dw_order_number='$tmp_order_number') AND status=$status";
        //echo "\nSQL: ".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row= mysql_fetch_object($res)) {
            $lista[] = $row->subinventory;
        }
        OMDBManager::closeConnection($con);
        return $lista;

    }

    public function deleteDelivery($dw_order_number, $subinventory) {
        $con = OMDBManager::getConnection();
        $tmp_order_number = ltrim($dw_order_number, '0');
        $sql ="DELETE FROM delivery_export WHERE (dw_order_number='$dw_order_number' OR  dw_order_number='$tmp_order_number') AND status=-1
        AND subinventory='$subinventory'";
        $res = mysql_query($sql);
        //echo "\nSQL:".$sql;
        OMDBManager::closeConnection($con);

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