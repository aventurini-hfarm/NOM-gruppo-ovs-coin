<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 21:49
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/QOHXMLAnalyzer.php";
require_once realpath(dirname(__FILE__))."/../../common/QOHInventoryFileManager.php";
require_once realpath(dirname(__FILE__))."/../../om2dw/stock/StockGenerator.php";

class QOHImport {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/import_qoh.log',KLogger::DEBUG);

    }

    public function start(){
        $this->log->LogInfo("Start importing inventory");
        $fileManager = new QOHInventoryFileManager();
        $lista_files = $fileManager->getFilesToProcess();
        $numero_files = 0;
        if (!$lista_files) {
            //nessun file da processare
            $this->log->LogInfo("Nessun file da processare");
        } else {
            //processa il file

            foreach ($lista_files as $file_name) {
                $this->processFile($file_name);
                $numero_files++;
            }
        }

        return $numero_files;
    }

    private function processFile($file_name){
        $this->log->LogInfo("Processing qoh file: ".$file_name);
        $path = $this->config->getQOHExportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("Processing qoh file (fullname): ".$full_name);
        MagentoInventoryHelper::setManualReindex();
        $processor = new QOHXMLAnalyzer($full_name);
        $processor->process();
        MagentoInventoryHelper::setAutomaticReindex();

        //muove il file nella cartella dei processati
        $dest_path = $this->config->getQOHExportArchiveDir()."/".$file_name;

        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogInfo("QOH file".$file_name." ARCHIVED");
            unlink($full_name);
        }

    }

}

$t = new QOHImport();
$numero_files= $t->start();
//Aggiunta la sezione sotto da Vincenzo 20102016
if ($numero_files>0) {
    $t = new StockGenerator();
    $t->run();
}
