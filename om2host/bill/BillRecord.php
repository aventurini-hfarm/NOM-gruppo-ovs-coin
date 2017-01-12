<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 17:03
 */

class BillRecord {

    private $lista_campi;

    function __construct($lista_campi) {
        $this->lista_campi = $lista_campi;
    }

    public function getLine() {
        return implode('|', $this->lista_campi)."|";
    }
} 