<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 21:49
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManagerDM.php";
require_once realpath(dirname(__FILE__))."/QOHXMLAnalyzerDM.php";
require_once realpath(dirname(__FILE__))."/../../common/QOHInventoryFileManagerDM.php";
require_once realpath(dirname(__FILE__))."/../../om2dw/stock/StockGenerator.php";


class QOHImportDM {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManagerDM();
        $this->log = new KLogger('/var/log/nom/import_qoh.log',KLogger::DEBUG);

    }

    public function start($id_magazzino){
        $this->log->LogInfo("Start importing inventory:".$id_magazzino);
        $fileManager = new QOHInventoryFileManagerDM();
        $lista_files = $fileManager->getFilesToProcess($id_magazzino);
        $numero_files = 0;
        if (!$lista_files) {
            //nessun file da processare
            $this->log->LogInfo("Nessun file da processare");
        } else {
            //processa il file
            foreach ($lista_files as $file_name) {
                $this->processFile($file_name, $id_magazzino);
                $numero_files++;
            }
        }
        return $numero_files;
    }

    private function processFile($file_name, $id_magazzino){
        $this->log->LogInfo("Processing qoh file: ".$file_name." magazzino: ".$id_magazzino);
        $path = $this->config->getQOHMAG1ExportInboundDir();
        if ($id_magazzino==2) $path = $this->config->getQOHMAG2ExportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("Processing qoh file (fullname): ".$full_name);
        MagentoInventoryHelperDM::setManualReindex();
        $processor = new QOHXMLAnalyzerDM($full_name);
        $processor->process($id_magazzino);
        MagentoInventoryHelperDM::setAutomaticReindex();

        //muove il file nella cartella dei processati
        $dest_path = $this->config->getQOHMAG1ExportArchiveDir()."/".$file_name;
        if ($id_magazzino==2) $dest_path = $this->config->getQOHMAG2ExportArchiveDir()."/".$file_name;

        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogInfo("QOH file".$file_name." ARCHIVED");
            unlink($full_name);
        }

    }

}

$t = new QOHImportDM();
$numero_files1 = $t->start(1); //import magazzio 1
echo "Numero_files1: ".$numero_files1;
$numero_files2 = $t->start(2); //import magazzio 2
echo "Numero_files2: ".$numero_files2;
//Aggiunta la sezione sotto da Vincenzo 20102016
$totale_files = $numero_files1 + $numero_files2;
echo "Totale files: ".$totale_files;

if ($totale_files>0) {
    echo "Inizia generazione";
    $t = new StockGenerator();
    $t->run();
}

