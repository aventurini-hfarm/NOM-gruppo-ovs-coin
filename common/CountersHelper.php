<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 02/05/15
 * Time: 14:50
 */

require_once realpath(dirname(__FILE__))."/OMDBManager.php";

class CountersHelper {


    public static function getDeliveyLineId(){

        $con = OMDBManager::getConnection();
        $res = mysql_query('SELECT `getNextCustomSeq`("seq_delivery_line_id") AS `getNextCustomSeq`');
        if($res === FALSE){ die(mysql_error()); }
        while ($row = mysql_fetch_object($res)) {

            $result = $row->getNextCustomSeq;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }

    public static function getDeliveyId() {
        $con = OMDBManager::getConnection();
        $res = mysql_query('SELECT `getNextCustomSeq`("seq_delivery_id") AS `getNextCustomSeq`');
        if($res === FALSE){ die(mysql_error()); }
        while ($row = mysql_fetch_object($res)) {

            $result = $row->getNextCustomSeq;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }

    public static function getTrxHeaderId() {
        $con = OMDBManager::getConnection();
        $res = mysql_query('SELECT `getNextCustomSeq`("seq_trx_header") AS `getNextCustomSeq`');
        if($res === FALSE){ die(mysql_error()); }
        while ($row = mysql_fetch_object($res)) {

            $result = $row->getNextCustomSeq;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }

    public static function getInvoiceNumber($year, $country) {
        $con = OMDBManager::getConnection();
        $country = strtolower($country);
        $res = mysql_query('SELECT `getNextCustomSeq`("seq_invoice_number_'.$country.'_'.$year.'") AS `getNextCustomSeq`');
        if($res === FALSE){ die(mysql_error()); }
        while ($row = mysql_fetch_object($res)) {

            $result = $row->getNextCustomSeq;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }

    /**
     * Usato per avere un numero unico di transazione nel flusso fatture
     * @param $year
     * @return mixed
     */
    public static function getTransactionNumber($year) {
        $con = OMDBManager::getConnection();
        $res = mysql_query('SELECT `getNextCustomSeq`("seq_transaction_number_'.$year.'") AS `getNextCustomSeq`');
        if($res === FALSE){ die(mysql_error()); }
        while ($row = mysql_fetch_object($res)) {

            $result = $row->getNextCustomSeq;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }

    public static function getStockReferenceNumber($year) {
        $con = OMDBManager::getConnection();
        $res = mysql_query('SELECT `getNextCustomSeq`("seq_stock_reference_number_'.$year.'") AS `getNextCustomSeq`');
        if($res === FALSE){ die(mysql_error()); }
        while ($row = mysql_fetch_object($res)) {

            $result = $row->getNextCustomSeq;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }

}

