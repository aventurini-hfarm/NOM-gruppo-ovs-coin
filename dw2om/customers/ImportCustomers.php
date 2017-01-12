<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 19:53
 */

require_once realpath(dirname(__FILE__)). "/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/CustomersFileManager.php";
require_once realpath(dirname(__FILE__))."/CustomerXMLAnalyzer.php";

class ImportCustomers {

    private $config;
    private $log;

    public function __construct(){
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/import_customers.log',KLogger::DEBUG);
    }

    public function start(){
        $this->log->LogInfo("Start importing customers");
        $customerFileManager = new CustomersFileManager();
        $lista_files = $customerFileManager->getFilesToProcess();
        if (!$lista_files) {
            //nessun file da processare
            $this->log->LogInfo( "Nessun file");
        } else {
            //processa il file
            foreach ($lista_files as $file_name)
                $this->processFile($file_name);
        }
    }

    private function processFile($file_name){
        $this->log->LogInfo("Processing: $file_name");
        $path = $this->config->getCustomerExportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("FullName: ".$full_name);
        $processor = new CustomerXMLAnalyzer($full_name);
        $processor->process();

        //muove il file nella cartella dei processati
        $dest_path = $this->config->getCustomerExportArchiveDir()."/".$file_name;
        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogInfo("Customer file".$file_name." ARCHIVED");
            unlink($full_name);
        }
    }
}

$t = new ImportCustomers();
$t->start();