<?php
/**
 * Created by PhpStorm.
 * User: vsambucaro
 * Date: 3/22/14
 * Time: 2:17 PM
 */

class AS400FileWriter {

    protected  $strutturaRecord;
    protected  $record;
    protected  $nomeFile;
    private    $recordSize;
    private    $handle;

    protected function setRecordSize($recordSize) {
        $this->recordSize = $recordSize;
        $this->resetRecord();
    }

    private function resetRecord() {
        $this->record = str_pad("",$this->recordSize," ");
        echo "\nRecSize: ($this->recordSize)-".strlen($this->record)."\n";
    }

    public function setField($fieldName, $value) {
        $this->assignField($fieldName, $value);
    }

    private function assignField($fieldName, $value) {


        foreach ($this->strutturaRecord->getFields() as $_field) {

            if ($_field->nome==$fieldName) {
                //$valoreCampo = strlen($value)>$_field->length ? substr($valore)
				if (strlen($value) > $_field->lunghezza) {

					$value = substr($value, 0, $_field->lunghezza);

				} 
                $this->record = substr_replace($this->record, $value,$_field->inizio-1, strlen($value));

                break;
            }
        }
    }

    public function getRecord() {
        return $this->record;
    }

    public function createFile() {
		$this->handle = fopen($this->nomeFile, (file_exists($this->nomeFile)) ? 'a' : 'w');
        //$this->handle = @fopen($this->nomeFile, "w");
        if (!$this->handle) {
            echo "Failed to create the file ($this->nomeFile)\n";
        }
    }

    public function writeRecord() {
        echo "\nRecord:".$this->record."\n";
        if (!$this->handle) { $this->createFile();}
        fwrite($this->handle,$this->record."\n");
        $this->resetRecord();
    }

    public function closeFile() {
        fclose($this->handle);
    }

} 