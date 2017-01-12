<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 21:49
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/DeliveryFileManager.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/DeliveryXMLAnalyzer.php";

class DeliveryImport {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/import_delivery.log',KLogger::DEBUG);

    }

    public function start(){
        $this->log->LogDebug ("Start importing deliveries");
        $fileManager = new DeliveryFileManager();
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
        echo "\nPRocessing file: ".$file_name;
        $this->log->LogInfo("Processing delivery file: ".$file_name);
        $path = $this->config->getDeliveryImportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("Processing delivery file (fullname): ".$full_name);
        $processor = new DeliveryXMLAnalyzer($full_name);
        $processor->process();
        $this->log->LogInfo("Delivery file: ".$file_name." processed");

        //muove il file nella cartella dei processati
        $dest_path = $this->config->getDeliveryImportArchiveDir()."/".$file_name;
        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogInfo("delivery file".$file_name." ARCHIVED");
            unlink($full_name);
        }

    }

}

$t = new DeliveryImport();
$t->start();