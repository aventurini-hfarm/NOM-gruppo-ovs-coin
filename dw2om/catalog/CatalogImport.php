<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 21:49
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/CatalogFileManager.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/CatalogXMLAnalyzer.php";

class CatalogImport {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/import_catalog.log',KLogger::DEBUG);

    }

    public function start(){
        $this->log->LogDebug ("Start importing catalog");
        $fileManager = new CatalogFileManager();
        $lista_files = $fileManager->getFilesToProcess();
        if (!$lista_files) {
            //nessun file da processare
            $this->log->LogDebug ( "Nessun file");
        } else {
            //processa il file
            foreach ($lista_files as $file_name)
                $this->processFile($file_name);
        }
    }

    private function processFile($file_name){
        $this->log->LogInfo("Processing catalog file: ".$file_name);
        $path = $this->config->getCatalogExportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("Processing catalog file (fullname): ".$full_name);
        $processor = new CatalogXMLAnalyzer($full_name);
        $processor->process();
        $this->log->LogInfo("Catalog file: ".$file_name." processed");

        //muove il file nella cartella dei processati
        $dest_path = $this->config->getCatalogExportArchiveDir()."/".$file_name;
        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogInfo("Catalog file".$file_name." ARCHIVED");
            unlink($full_name);
        }

    }

}

$t = new CatalogImport();
$t->start();