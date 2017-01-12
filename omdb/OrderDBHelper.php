<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/05/15
 * Time: 16:27
 */

require_once realpath(dirname(__FILE__)) . "/../common/OMDBManager.php";

require_once realpath(dirname(__FILE__))."/OMDBConstant.php";

class OrderDBHelper {

    private $order_number;

    public function __construct($order_number){
        $this->order_number = $order_number;
    }

    public function addShippingPromotion($promotion_id, $campaign_id, $value){
        $con = OMDBManager::getConnection();

        $type = SHIPPING_PROMO;
        $this->_addPromo($type, $promotion_id, $campaign_id, $value);
    }

    public function addMerchandizePromotion($promotion_id, $campaign_id, $value){
        $type = MERCHANDIZE_PROMO;
        $this->_addPromo($type, $promotion_id, $campaign_id, $value);
    }

    private function _addPromo($type, $promotion_id, $campaign_id, $value) {
        $con = OMDBManager::getConnection();

        //cancella vecchia promo
        $sql ="DELETE FROM promotions WHERE order_number='$this->order_number' AND promotion_id='$promotion_id' AND type=".$type ;
        $res = mysql_query($sql);

        //adesso inserisce la promo
        $sql="INSERT INTO promotions (order_number, promotion_id, campaign_id, value, type)
        VALUES ('$this->order_number', '$promotion_id', '$campaign_id', '$value', '$type')";
        $res = mysql_query($sql);

        OMDBManager::closeConnection($con);
    }

    public function resetShippingPromotion() {
        $type = SHIPPING_PROMO;
        $this->_resetPromotion($type);
    }

    public function resetMerchandizePromotion() {
        $type = MERCHANDIZE_PROMO;
        $this->_resetPromotion($type);
    }

    private function _resetPromotion($type) {
        $con = OMDBManager::getConnection();

        //cancella vecchia promo
        $sql ="DELETE FROM promotions WHERE order_number='$this->order_number'  AND type=".$type ;
        $res = mysql_query($sql);

        OMDBManager::closeConnection($con);
    }


    public function addCustomAttributes($customFields) {
        $con = OMDBManager::getConnection();

        //cancella vecchia promo
        $sql ="DELETE FROM order_custom_attributes WHERE dw_order_no='$this->order_number'";
        $res = mysql_query($sql);

        foreach ($customFields as $key=>$value) {
            $valore = (string)$value;
            $sql="INSERT INTO order_custom_attributes (dw_order_no, custom_attribute, value)
        VALUES ('$this->order_number', '$key', '$valore')";
            $res = mysql_query($sql);
        }




        OMDBManager::closeConnection($con);
    }


    public function getCustomAttributes() {
        $con = OMDBManager::getConnection();

        //cancella vecchia promo
        $sql ="SELECT * FROM order_custom_attributes WHERE dw_order_no='$this->order_number'";
        //echo "\nSQL:".$sql;
        $res = mysql_query($sql);

        $result = array();
        while ($row=mysql_fetch_object($res)) {
            $result[$row->custom_attribute] = $row->value;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }

    private function _getPromo($type) {
        $con = OMDBManager::getConnection();

        //cancella vecchia promo
        $sql ="SELECT * FROM promotions WHERE order_number='$this->order_number' AND type=".$type;
        //echo "\nSQL:".$sql;
        $res = mysql_query($sql);
        $lista_promo = array();


        while ($row = mysql_fetch_object($res)) {
            $promoObj = new stdClass();
            $promoObj->promotion_id = $row->promotion_id;
            $promoObj->campaign_id = $row->campaign_id;
            $promoObj->value = $row->value;
            $lista_promo[] = $promoObj;
        }

        OMDBManager::closeConnection($con);

        return $lista_promo;
    }

    public function getMerchandizePromotion() {
        return $this->_getPromo(MERCHANDIZE_PROMO);
    }

    public function getShippingPromotion() {
        $res =  $this->_getPromo(SHIPPING_PROMO);
        if (sizeof($res)==0) {
            $promoObj = new stdClass();
            $promoObj->promotion_id = '';
            $promoObj->campaign_id = '';
            $promoObj->value = 0;
            $res = array($promoObj);
        }

        return $res;
    }

    public function getItemOptions($item_id) {
        $con = OMDBManager::getConnection();
        $sql = "SELECT * FROM option_lineitems WHERE order_no='$this->order_number' AND product_id='$item_id'";
        $res = mysql_query($sql);
        $lista_valori = array();

        while ($row = mysql_fetch_object($res)) {
            $record = new stdClass();
            $record->option_key = $row->option_key;
            $record->option_value = $row->option_value;
            $lista_valori[$record->option_key] = $record;
        }

        OMDBManager::closeConnection($con);
        return $lista_valori;
    }

    public function addItemOptions($item_id, $records) {
        $con = OMDBManager::getConnection();
        $sql = "DELETE FROM option_lineitems WHERE order_no='$this->order_number' AND product_id='$item_id'";

        foreach ($records as $record) {
            $sql = "INSERT INTO option_lineitems (order_no, product_id, option_key, option_value)
            VALUES ('$this->order_number', '$item_id','$record->key','$record->value')";
           // echo "\nSQL:".$sql;
            $res = mysql_query($sql);
        }
        OMDBManager::closeConnection($con);
    }

} 