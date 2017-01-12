<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 21:49
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManagerDM.php";
require_once realpath(dirname(__FILE__))."/QOHXMLAnalyzerSubInventory.php";
require_once realpath(dirname(__FILE__))."/../../common/QOHInventoryFileManagerDM.php";
require_once realpath(dirname(__FILE__))."/../../om2dw/stock/StockGenerator.php";


class QOHImportSubInventory {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManagerDM();
        $this->log = new KLogger('/var/log/nom/import_qoh.log',KLogger::DEBUG);

    }

    public function processManuale($nome_file, $id_magazzino) {
        $this->processFile($nome_file, $id_magazzino);
    }

    private function processFile($file_name, $id_magazzino){

        echo "\nProcessing qoh file: ".$file_name." magazzino: ".$id_magazzino;
        $full_name=$file_name;
        echo "\nProcessing qoh file (fullname): ".$full_name;
        MagentoInventoryHelperDM::setManualReindex();
        $processor = new QOHXMLAnalyzerSubInventory($full_name);
        $processor->process($id_magazzino);
        MagentoInventoryHelperDM::setAutomaticReindex();


    }

}

$t = new QOHImportSubInventory();
$t->processManuale("/home/OrderManagement/testFiles/inventory_export/inbound/archive/qoh_ov_it_WH_SG_20161118013000.xml",1);

