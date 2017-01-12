<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/05/15
 * Time: 16:01
 */
require_once realpath(dirname(__FILE__)) . "/../common/OMDBManager.php";
require_once realpath(dirname(__FILE__))."/OMDBConstant.php";

class ShipmentDBHelper {

    private $order_number;

    public function __construct($order_number = null){
        $this->order_number = $order_number;
    }


    public function addShipment(ShippingObject $shippingObject) {
        $con = OMDBManager::getConnection();
        $order_num = $shippingObject->order_no;
        $ncolli = $shippingObject->ncolli;
        $delivery_id = $shippingObject->delivery_id;

        $delivery_date = $shippingObject->delivery_date;
        $delivery_date = date("Y-m-d", strtotime(substr($shippingObject->delivery_date,0,10)));

        $shipping_date = $shippingObject->shipping_date;
        $shipping_date = date("Y-m-d", strtotime(substr($shippingObject->shipping_date,0,10)));

        $subinventory = $shippingObject->subinventory;
        $lettera_vettura = $shippingObject->lettera_vettura;
        $esito = $shippingObject->esito;
        $first_track = $shippingObject->first_track;
        $last_track = $shippingObject->last_track;
        $list_track = $shippingObject->list_track;
        $shipment_note = $shippingObject->shipment_note;

        $sql = "DELETE FROM shipment WHERE delivery_id='$delivery_id' AND subinventory='$subinventory'";
        $res = mysql_query($sql);

        $sql ="INSERT INTO shipment (order_num, ncolli, delivery_id, delivery_date, shipping_date, subinventory,
        lettera_vettura, esito, first_track, last_track, list_track, shipment_note)
        VALUES ('$order_num', '$ncolli','$delivery_id', '$delivery_date','$shipping_date','$subinventory',
        '$lettera_vettura','$esito', '$first_track', '$last_track', '$list_track', '$shipment_note')";
        //echo "\nSQL SHIPMENT: ".$sql;

        $res = mysql_query($sql);
        if (!$res) {
            //echo "\nFallito insert shipment";
        }

        $sql = "UPDATE delivery SET esito='$esito' WHERE delivery_id='$delivery_id' AND order_number='$order_num'";
        $res = mysql_query($sql);

        OMDBManager::closeConnection($con);
    }

    public function _isOrderShipped($order_number, $delivery_id) {
        $con = OMDBManager::getConnection();
        $status = ORDER_DELIVERY_STATUS_VALID;
        $tmp_order_number = ltrim($order_number, '0');
        //$sql = "SELECT s.esito  FROM delivery d, shipment s WHERE (d.order_number='$order_number' OR  d.order_number='$tmp_order_number')
        //AND status=$status AND d.delivery_id=s.delivery_id";
        $sql = "SELECT s.esito  FROM delivery d, shipment s WHERE (d.order_number='$order_number' OR  d.order_number='$tmp_order_number')
        AND status=$status AND d.delivery_id=s.delivery_id AND s.delivery_id = '$delivery_id'";

        //echo "\nSQL : ".$sql;
        $res = mysql_query($sql);
        $all_shipped = true;
        $received_shipment = false;
        while ($row = mysql_fetch_object($res)) {
            $received_shipment = true;
            if ($row->esito != 1) $all_shipped = false;
            //echo "\nEsito: ".$row->esito;
        }
        OMDBManager::closeConnection($con);
        //echo "\nReceived_shipment: ".$received_shipment;
        //echo "\nAll Shipped: ".$all_shipped;
        return ($all_shipped && $received_shipment);
    }

    public function isOrderShipped($order_number, $delivery_id) {
        $con = OMDBManager::getConnection();
        $status = ORDER_DELIVERY_STATUS_VALID;
        $tmp_order_number = ltrim($order_number, '0');
        $sql = "SELECT esito FROM delivery d  WHERE (d.order_number='$order_number' OR  d.order_number='$tmp_order_number')
        AND status=1";

        //echo "\nSQL : ".$sql;
        $res = mysql_query($sql);
        $all_shipped = true;
        $received_shipment = false;
        while ($row = mysql_fetch_object($res)) {
             $received_shipment = true;
            if ($row->esito != 1) $all_shipped = false;
            //echo "\nEsito: ".$row->esito;
        }
        OMDBManager::closeConnection($con);
        //echo "\nReceived_shipment: ".$received_shipment;
        //echo "\nAll Shipped: ".$all_shipped;
        return ($all_shipped && $received_shipment);
    }



    public function getShipment($order_no) {
        $con = OMDBManager::getConnection();
        $tmp_order_number = ltrim($order_no, '0');
        $sql = "SELECT * FROM shipment WHERE (order_num = '$order_no' OR order_num = '$tmp_order_number')
        ORDER BY delivery_id DESC LIMIT 1";
        $res = mysql_query($sql);
        $obj = new stdClass();
        $found = false;
        while ($row = mysql_fetch_object($res)) {
            $found = true;
            $obj->shipping_date = $row->shipping_date;
            $obj->first_track = $row->first_track;
            $obj->last_track = $row->last_track;
            $obj->list_track = $row->list_track;
            $obj->delivery_date = $row->delivery_date;
            $obj->ncolli = $row->ncolli;
        }

        if (!$found) return null;
        return $obj;
    }

    public function getListaShipmentsPositivi($order_no) {
        $con = OMDBManager::getConnection();
        $tmp_order_number = ltrim($order_no, '0');
        $sql = "SELECT * FROM shipment WHERE (order_num = '$order_no' OR order_num = '$tmp_order_number') AND esito=1
        ORDER BY delivery_id ASC ";
        $res = mysql_query($sql);

        $found = false;
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $found = true;
            $obj = new stdClass();
            $obj->shipping_date = $row->shipping_date;
            $obj->first_track = $row->first_track;
            $obj->last_track = $row->last_track;
            $obj->list_track = $row->list_track;
            $obj->delivery_date = $row->delivery_date;
            $obj->ncolli = $row->ncolli;
            $lista[] = $obj;
        }

        if (!$found) return null;
        return $lista;
    }

    public function addCustomAttributes($customFields) {
        $con = OMDBManager::getConnection();

        //cancella vecchia
        $sql ="DELETE FROM shipment_custom_attributes WHERE dw_order_no='$this->order_number'";
        $res = mysql_query($sql);

        foreach ($customFields as $key=>$value) {
            $valore = mysql_real_escape_string((string)$value);
            $sql="INSERT INTO shipment_custom_attributes (dw_order_no, custom_attribute, value)
        VALUES ('$this->order_number', '$key', '$valore')";
           // echo "\nSQL: ".$sql;
            $res = mysql_query($sql);

            if (!$res) {
                echo "\nErrore ShipmentDbHelper: addCustomAttributes: ".$sql;
            }
        }




        OMDBManager::closeConnection($con);
    }


    public function getCustomAttributes() {
        $con = OMDBManager::getConnection();


        $sql ="SELECT * FROM shipment_custom_attributes WHERE dw_order_no like '%$this->order_number'";
        //echo "\nSQL:".$sql;
        $res = mysql_query($sql);

        $result = array();
        while ($row=mysql_fetch_object($res)) {
            $result[$row->custom_attribute] = $row->value;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }
} 