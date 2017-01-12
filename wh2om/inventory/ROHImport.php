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
require_once realpath(dirname(__FILE__))."/../../common/ROHInventoryFileManager.php";
require_once realpath(dirname(__FILE__))."/MagentoInventoryHelper.php";
require_once realpath(dirname(__FILE__))."/../../om2dw/stock/StockGenerator.php";
require_once realpath(dirname(__FILE__))."/ROHXMLAnalyzer.php";


class ROHImport {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/import_roh.log',KLogger::DEBUG);

    }

    public function start(){
        $this->log->LogInfo("Start importing inventory (roh)");
        $fileManager = new ROHInventoryFileManager();
        $lista_files = $fileManager->getFilesToProcess();
        if (!$lista_files) {
            //nessun file da processare
            $this->log->LogInfo("Nessun file da processare");
        } else {
            //processa il file
            foreach ($lista_files as $file_name)
                $this->processFile($file_name);
        }
    }

    private function processFile($file_name){
        $this->log->LogInfo("Processing roh file: ".$file_name);
        $path = $this->config->getROHExportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("Processing roh file (fullname): ".$full_name);
        MagentoInventoryHelper::setManualReindex();
        $processor = new ROHXMLAnalyzer($full_name);
        $sku_list = $processor->process();
        MagentoInventoryHelper::setAutomaticReindex();

        $list = array_filter($sku_list);

        if (!empty($list)) {
            //export stock
            $this->log->logDebug('Creating Stock file due to ROH');
            //echo "\nLISTA";
            //print_r($list);
            $stockGenerator = new StockGenerator();
            $stockGenerator->run($list);
            $this->log->logDebug('Stock file due to ROH -> CRATED');
        }

        $this->log->LogInfo("roh file: ".$file_name." PROCESSED");

        //muove il file nella cartella dei processati
        $dest_path = $this->config->getROHExportArchiveDir()."/".$file_name;

        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogInfo("ROH file".$file_name." ARCHIVED");
            unlink($full_name);
        }

    }

}

$t = new ROHImport();
$t->start();