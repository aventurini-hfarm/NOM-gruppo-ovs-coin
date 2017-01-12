<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:34
 */
require_once realpath(dirname(__FILE__))."/BillRecord.php";

class RegisterOpenRecord extends BillRecord {


    function __construct($codice_negozio, $data_elaborazione) {
        $tipo_record = "REGISTER_OPEN";
        $lista = array($tipo_record, $codice_negozio, $data_elaborazione);
        parent::__construct($lista);
    }

}

