<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:34
 */
require_once realpath(dirname(__FILE__))."/BillRecord.php";

class ItemFeeRecord extends BillRecord {


    function __construct($codice_articolo_spedizione, $valore_spese_spedizione, $sconto_spese_spedizione, $codice_promozione) {
        $tipo_record = "ITEM_FEE";
        $lista = array($tipo_record, $codice_articolo_spedizione, $valore_spese_spedizione, $sconto_spese_spedizione, $codice_promozione);
        parent::__construct($lista);
    }

}

