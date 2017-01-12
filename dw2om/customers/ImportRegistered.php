<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 19:53
 */

require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/RegisteredFileManager.php";
require_once "CustomerXMLAnalyzer.php";

class ImportCustomers {

    private $config;

    public function __construct(){
        $this->config = new ConfigManager();
        $this->log = new KLogger('import_registered.log',KLogger::DEBUG);
    }

    public function start(){
        echo "\nStart importing registered";
        $customerFileManager = new RegisteredFileManager();
        $lista_files = $customerFileManager->getFilesToProcess();
        if (!$lista_files) {
            //nessun file da processare
            echo "\nNessun file";
        } else {
            //processa il file
            foreach ($lista_files as $file_name)
                $this->processFile($file_name);
        }
    }

    private function processFile($file_name){
        echo "\nProcessing: $file_name";
        $path = $this->config->getCustomerExportInboundDir();
        $full_name = $path."/".$file_name;
        echo "\nFullName: ".$full_name;
        $processor = new CustomerXMLAnalyzer($full_name);
        $processor->process();

        //muove il file nella cartella dei processati
        $dest_path = $this->config->getCustomerExportArchiveDir()."/".$file_name;
        echo "Copio: ".$full_name."-> ".$dest_path;
        if (copy($full_name, $dest_path)) {
            $this->log->LogDebug("Registered file".$file_name." ARCHIVED");
            unlink($full_name);
        }
    }
}

$t = new ImportCustomers();
$t->start();