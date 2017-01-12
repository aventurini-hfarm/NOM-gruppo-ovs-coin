<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 01/05/15
 * Time: 19:29
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";

class FileGenerator {

    public $lines;
    private $handle;

    private $log;
    public function __construct()
    {

        $this->log = new KLogger('/var/log/nom/file_generator_delivery_export.log',KLogger::DEBUG);

    }

    public function getLines() {
        foreach ($this->lines as $line) {
            //echo "\n$line";
        }
    }

    public function createFile($nomeFile) {
        $this->handle = fopen($nomeFile,  'w');

        //$this->handle = @fopen($this->nomeFile, "w");
        if (!$this->handle) {
            //echo "Failed to create the file ($this->nomeFile)\n";
            $this->log->LogError('Failed to create the file ($this->nomeFile)');
        }
    }

    public function writeRecord($content) {
        if (is_array($content))
            foreach ($content as $line) {
                fwrite($this->handle, $line."\n");
            }
        else
            fwrite($this->handle, $content."\n");



    }

    public function closeFile() {
        fclose($this->handle);
    }

    public function removeFile($filename) {
        unlink($filename);
    }

} 