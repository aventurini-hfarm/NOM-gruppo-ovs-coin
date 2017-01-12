<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 10/05/15
 * Time: 16:27
 */

require_once realpath(dirname(__FILE__)) . "/../common/OMDBManager.php";

require_once realpath(dirname(__FILE__))."/OMDBConstant.php";

class CountryDBHelper {


    public static function getCountryDetails($country_id){
        $con = OMDBManager::getConnection();
        $country_id = strtolower($country_id);

        //cancella vecchia promo
        $sql ="SELECT * FROM estero_light WHERE country_id='$country_id'";
        //echo "\nSQL:".$sql;
        $res = mysql_query($sql);

        $result = null;
        while ($row=mysql_fetch_object($res)) {
            $result = new stdClass();
            $result->country_id = $country_id;
            $result->corriere = $row->corriere;
            $result->soglia = $row->soglia;
            $result->sopra_soglia = $row->sopra_soglia;
            $result->codice_ente = $row->codice_ente;
            $result->nome = $row->nome;
            $result->rappr_fiscale =  $row->rappr_fiscale;
            $result->iva = $row->iva;                                       // RINO 13/09/2016

            $result->codice_iva = $row->codice_iva;                         //RINO 22/09/2016
            $result->tipo_documento_vendita = $row->tipo_documento_vendita; //RINO 22/09/2016
            $result->tipo_documento_reso = $row->tipo_documento_reso;       //RINO 22/09/2016
            $result->aliquota_iva = $row->aliquota_iva;                     //RINO 22/09/2016

            $result->registro_iva = $row->registro_iva;                     //RINO 23/09/2016

            $result->header_it = $row->header_it;
            $result->header_en = $row->header_en;
            $result->header_es = $row->header_es;

            $result->footer_it = $row->footer_it;
            $result->footer_en = $row->footer_en;
            $result->footer_es = $row->footer_es;

        }

        OMDBManager::closeConnection($con);
        //<print_r($result);
        return $result;

    }

    public static function getCountries(){   //Rino 9/07/2016
        $con = OMDBManager::getConnection();

        $sql ="SELECT * FROM estero_light ";
        $res = mysql_query($sql);

        $results = array();
        while ($row=mysql_fetch_object($res)) {
            $result = new stdClass();
            $result->country_id = strtolower($row->country_id);
            $result->corriere = $row->corriere;
            $result->soglia = $row->soglia;
            $result->sopra_soglia = $row->sopra_soglia;
            $result->codice_ente = $row->codice_ente;
            $result->nome = $row->nome;
            $result->rappr_fiscale =  $row->rappr_fiscale;
            array_push($results,$result);
        }

        OMDBManager::closeConnection($con);

        return $results;

    }

    public static function getEnti(){   //Rino 19/09/2016
        $con = OMDBManager::getConnection();

        $sql ="SELECT DISTINCT codice_ente  FROM estero_light order by priorita";
        $res = mysql_query($sql);

        $results = array();
        while ($row=mysql_fetch_object($res)) {
            $result = new stdClass();
            $result->codice_ente = $row->codice_ente;
            array_push($results,$result);
        }

        OMDBManager::closeConnection($con);

        return $results;

    }

}

