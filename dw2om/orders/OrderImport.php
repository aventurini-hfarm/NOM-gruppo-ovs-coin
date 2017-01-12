<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 21:49
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/OrderXMLAnalyzer.php";
require_once realpath(dirname(__FILE__))."/../../common/OrderFileManager.php";

class OrderImport {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/import_order.log',KLogger::DEBUG);

    }


    public function start(){
        $this->log->LogInfo("Start importing order");
        $fileManager = new OrderFileManager();
        $lista_files = $fileManager->getFilesToProcess();
        if (!$lista_files) {
            //nessun file da processare
            $this->log->LogInfo("Nessun file da processare");
        } else {
            foreach ($lista_files as $file_name)
                $this->processFile($file_name);
        }
    }

    private function processFile($file_name){
        $this->log->LogInfo("Processing order file: ".$file_name);
        $path = $this->config->getOrderExportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("Processing order file (fullname): ".$full_name);
        $processor = new OrderXMLAnalyzer($full_name);
        $processor->process();
        $this->log->LogInfo("Order file: ".$file_name." PROCESSED");
        //muove il file nella cartella dei processati
        $dest_path = $this->config->getOrderExportArchiveDir()."/".$file_name;
        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogInfo("Order file".$file_name." ARCHIVED");
            unlink($full_name);
        }

    }

}

$t = new OrderImport();
$t->start();