<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:34
 */
require_once realpath(dirname(__FILE__))."/BillRecord.php";

class ItemTenderRecord extends BillRecord {


    function __construct($tipo_pagamento, $circuito_cc, $importo) {
        $tipo_record = "ITEM_TENDER";
        $lista = array($tipo_record, $tipo_pagamento, $circuito_cc, $importo);
        parent::__construct($lista);
    }

}

