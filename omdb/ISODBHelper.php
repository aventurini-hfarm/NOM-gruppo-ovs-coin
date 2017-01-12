<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/05/15
 * Time: 16:27
 */

require_once realpath(dirname(__FILE__)) . "/../common/OMDBManager.php";

require_once realpath(dirname(__FILE__))."/OMDBConstant.php";

class ISODBHelper {

/*
    public static function getISODetails($country_id, $nome){
        $con = OMDBManager::getConnection();
        $country_id = strtoupper($country_id);

        //cancella vecchia promo
        $sql ="SELECT * FROM codici_iso WHERE country_id='$country_id' AND nome = '$nome'";
        echo "\nSQL:".$sql;
        $res = mysql_query($sql);

        $result = null;
        while ($row=mysql_fetch_object($res)) {
            $result = new stdClass();
            $result->country_id = $country_id;
            $result->codice_iso = $row->codice_iso;
            $result->nome = $row->nome;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }
*/

    public static function getISODetails($country_unique_code){
        $con = OMDBManager::getConnection();
        $country_id = strtoupper($country_unique_code);

        //cancella vecchia promo
        $sql ="SELECT * FROM codici_iso WHERE codice_iso='$country_unique_code' ";
        //echo "\nSQL:".$sql;
        $res = mysql_query($sql);

        $result = null;
        while ($row=mysql_fetch_object($res)) {
            $result = new stdClass();
            $result->country_id = $country_id;
            $result->codice_iso = $row->codice_iso;
            $result->nome = $row->nome;
        }

        OMDBManager::closeConnection($con);

        return $result;

    }


}

