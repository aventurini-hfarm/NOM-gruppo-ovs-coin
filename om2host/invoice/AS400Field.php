<?php
/**
 * Created by PhpStorm.
 * User: vsambucaro
 * Date: 3/17/14
 * Time: 11:22 PM
 */

class AS400Field {
    public $nome;
    public $inizio;
    public $fine;
    public $lunghezza;

    public function __construct($_nome, $_inizio, $_fine, $_lunghezza) {
        $this->nome = $_nome;
        $this->inizio = $_inizio;
        $this->fine = $_fine;
        $this->lunghezza = $_lunghezza;

    }


} 