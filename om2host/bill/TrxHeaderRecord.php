<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:34
 */
require_once realpath(dirname(__FILE__))."/BillRecord.php";

class TrxHeaderRecord extends BillRecord {


    function __construct($codice_cassa, $unique_id, $trx_date, $tipo_transazione, $numero_tessera_fidelity, $punti_guadagnati, $punti_spesi,
    $cap_cliente, $valuta_transazione, $codice_cliente_magento, $codice_cliente_web, $numero_ordine, $data_ordine, $esenzione_iva) {
        $tipo_record = "TRX_HEADER";
        $lista = array($tipo_record, $codice_cassa, $unique_id, $trx_date, $tipo_transazione, $numero_tessera_fidelity, $punti_guadagnati, $punti_spesi,
            $cap_cliente, $valuta_transazione, $codice_cliente_magento, $codice_cliente_web, $numero_ordine, $data_ordine, $esenzione_iva);
        parent::__construct($lista);
    }

}

