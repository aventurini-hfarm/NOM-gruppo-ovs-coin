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


class ROHImportDM {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/import_roh.log',KLogger::DEBUG);

    }

    public function start($id_magazzino){
        $this->log->LogInfo("Start importing inventory (roh) , magazzino: ".$id_magazzino);
        $fileManager = new ROHInventoryFileManagerDM();
        $lista_files = $fileManager->getFilesToProcess($id_magazzino);
        if (!$lista_files) {
            //nessun file da processare
            $this->log->LogInfo("Nessun file da processare");
        } else {
            //processa il file
            foreach ($lista_files as $file_name)
                $this->processFile($file_name, $id_magazzino);
        }
    }

    private function processFile($file_name, $id_magazzino){
        $this->log->LogInfo("Processing roh file: ".$file_name." magazzino: ".$id_magazzino);
        $path = $this->config->getROHMAG1ExportInboundDir();
        if ($id_magazzino==2) $path = $this->config->getROHMAG2ExportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("Processing roh file (fullname): ".$full_name);
        MagentoInventoryHelper::setManualReindex();
        $processor = new ROHXMLAnalyzerDM($full_name);
        $sku_list = $processor->process($id_magazzino);
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
        $dest_path = $this->config->getROHMAG1ExportArchiveDir()."/".$file_name;
        if ($id_magazzino==2) $path = $this->config->getROHMAG2ExportArchiveDir();


        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogInfo("ROH file".$file_name." ARCHIVED");
            unlink($full_name);
        }

    }

}

$t = new ROHImport();
$t->start(1); //magazzino 1
$t->start(2); //magazzino 2