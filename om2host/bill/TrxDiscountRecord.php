<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:34
 */
require_once realpath(dirname(__FILE__))."/BillRecord.php";

class TrxDiscountRecord extends BillRecord {


    function __construct($valore_sconto, $codice_promozione) {
        $tipo_record = "TRX_DISCOUNT";
        $lista = array($tipo_record, $valore_sconto, $codice_promozione);
        parent::__construct($lista);
    }

}

