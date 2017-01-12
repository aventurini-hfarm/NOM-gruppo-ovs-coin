<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:34
 */
require_once realpath(dirname(__FILE__))."/BillRecord.php";

class ItemStockRecord extends BillRecord {


    function __construct($sku, $prezzo_unitario, $quantità, $sconto, $punti_extra, $punti_resi, $codice_promozione) {
        $tipo_record = "ITEM_STOCK";
        $lista = array($tipo_record, $sku, $prezzo_unitario, $quantità, $sconto, $punti_extra, $punti_resi, $codice_promozione);
        parent::__construct($lista);
    }

}

