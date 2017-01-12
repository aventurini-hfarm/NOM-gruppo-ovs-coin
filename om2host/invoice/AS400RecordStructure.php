<?php
/**
 * Created by PhpStorm.
 * User: vsambucaro
 * Date: 3/17/14
 * Time: 11:53 PM
 */

class AS400RecordStructure {
    private $strutturaRecord = array();

    public function __construct() {

    }

    public function addField(AS400Field $_field) {
        array_push($this->strutturaRecord,  $_field);
    }

    public function getRecordLength() {
        $counter = 0;
        foreach ($this->strutturaRecord as $_field) {
            $counter += $_field->lunghezza;
        }

        return $counter;
    }

    public function getFieldsByRecord($record) {
        $ret = array();
        foreach ($this->strutturaRecord as $_field) {
            $valore = substr($record, $_field->inizio-1, $_field->lunghezza);
            $ret[$_field->nome]= utf8_encode($valore);
        }
        return $ret;
    }

    public function getFields() {
        return $this->strutturaRecord;
    }

} 