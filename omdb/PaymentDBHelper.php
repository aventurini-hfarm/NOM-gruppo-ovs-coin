<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/05/15
 * Time: 16:27
 */

require_once realpath(dirname(__FILE__)) . "/../common/OMDBManager.php";

require_once realpath(dirname(__FILE__))."/OMDBConstant.php";
require_once realpath(dirname(__FILE__))."/../common/KLogger.php";


class PaymentDBHelper {

    private $order_number;
    private $log;

    public function __construct($order_number){
        $this->order_number = $order_number;
        $this->log = new KLogger('/var/log/nom/payment_dbhelper.log',KLogger::DEBUG);
    }

    /**
     * Parametri relativi al metodo di pagamento provenienti dall'ordine
     * @param $payment_method
     * @param $ref_code
     * @param $auth_id
     * @param $auth_token
     * @param $amount
     */
    public function addPaymentInfo($payment_method, $ref_code, $auth_id, $auth_token, $amount) {
        $con = OMDBManager::getConnection();
        //cancella vecchia promo
        $sql ="DELETE FROM payments WHERE order_number='$this->order_number'";
        $res = mysql_query($sql);

        //adesso inserisce la promo
        $timestamp_transazione = date("Y-m-d H:i:s");
        $sql="INSERT INTO payments (order_number, payment_method, ref_code, auth_id, auth_token ,amount, trx_timestamp)
        VALUES ('$this->order_number', '$payment_method', '$ref_code', '$auth_id', '$auth_token', '$amount', '$timestamp_transazione')";
        //echo "\nSQL: ".$sql;
        $res = mysql_query($sql);

        OMDBManager::closeConnection($con);
    }

    /**
     * Aggiorna lo status code e descrizione del pagamento (capture)
     * @param $reason_code
     * @param $reason_description
     */
    public function updateDoCaptureResponse($reason_code, $reason_description, $refund_trxid=''){
        $con = OMDBManager::getConnection();
        $timestamp_transazione = date("Y-m-d H:i:s");
        $sql ="UPDATE payments SET reason_code = '$reason_code' ,
         reason_description='$reason_description',
          trx_timestamp='$timestamp_transazione',
          refund_trxid='$refund_trxid'
         WHERE order_number='$this->order_number'";
       // echo "\nSQL UpdateDoCapture: ".$sql;
        $this->log->LogDebug("UpdateDoCaputre: ".$sql);
        $res = mysql_query($sql);
        OMDBManager::closeConnection($con);
    }

    public function updateDoRefundResponse($reason_code, $reason_description){
        $con = OMDBManager::getConnection();
        $timestamp_transazione = date("Y-m-d H:i:s");
        $sql ="UPDATE payments SET refund_reason_code = '$reason_code' ,
         refund_reason_description='$reason_description',
          trx_timestamp='$timestamp_transazione'
         WHERE order_number='$this->order_number'";
        // echo "\nSQL UpdateDoCapture: ".$sql;
        $res = mysql_query($sql);
        OMDBManager::closeConnection($con);
    }

    /**
     * Ritorna i dettagli della transazione di pagamento
     * @return stdClass
     */
    public function getPaymentInfo(){
        $con = OMDBManager::getConnection();
        $sql = "SELECT * FROM payments WHERE order_number='$this->order_number'";

        $res = mysql_query($sql);
        $paymentObj = null;
        while ($row = mysql_fetch_object($res)) {
            $paymentObj = new stdClass();
            $paymentObj->payment_method = $row->payment_method;
            $paymentObj->ref_code = $row->ref_code;
            $paymentObj->auth_id = $row->auth_id;
            $paymentObj->auth_token = $row->auth_token;
            $paymentObj->amount = $row->amount;
            $paymentObj->reason_code = $row->reason_code;
            $paymentObj->reason_description = $row->reason_description;
            $paymentObj->timestamp = $row->trx_timestamp;
            $paymentObj->refund_trxid = $row->refund_trxid;
            $paymentObj->refund_reason_code = $row->refund_reason_code;                 // RINO 21/09/2016
            $paymentObj->refund_reason_description = $row->refund_reason_description;   // RINO 21/09/2016
        }

        OMDBManager::closeConnection($con);
        return $paymentObj;
    }


} 