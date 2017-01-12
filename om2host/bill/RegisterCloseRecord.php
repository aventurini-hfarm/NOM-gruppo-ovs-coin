<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:34
 */
require_once realpath(dirname(__FILE__))."/BillRecord.php";

class RegisterCloseRecord extends BillRecord {


    function __construct($data_fine_elaborazione) {
        $tipo_record = "REGISTER_CLOSE";
        $lista = array($tipo_record, $data_fine_elaborazione);
        parent::__construct($lista);
    }

}

