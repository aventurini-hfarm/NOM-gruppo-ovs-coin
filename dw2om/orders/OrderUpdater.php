<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 21:49
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/OrderXMLAnalyzerUpdate.php";
require_once realpath(dirname(__FILE__))."/../../common/OrderFileManager.php";

class OrderUpdater {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/import_order.log',KLogger::DEBUG);

    }

    public function getFiles()
    {
        $directory = "/home/OrderManagement/testFiles/order_to_update";
        $iterator = new FilesystemIterator($directory);
        $reg_exp = "/^.*order_[a-zA-Z]{2}_([a-zA-Z]{2})_DW_SG_[0-9]{14}.xml$/i";
        $filter = new RegexIterator($iterator, $reg_exp);
        //$iterator = new FilesystemIterator("/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/customer_export/inbound/");
        //$filter = new RegexIterator($iterator, '/^.*-customer_[a-zA-Z]{2}_[a-zA-Z]{2}_DW_SG_[0-9]{14}.xml$/i');

        $filelist = array();
        foreach($filter as $entry) {
            $filelist[] = $entry->getFilename();
        }


        return $filelist;
    }

    public function start(){
        echo "\nStart importing order to update\n";

        $lista_files = $this->getFiles();
        $counter = 0;
        if (!$lista_files) {
            //nessun file da processare
            $this->log->LogInfo("Nessun file da processare");
        } else {
            foreach ($lista_files as $file_name) {
                $counter++;
                $this->processFile($file_name,$counter);
            }
        }
    }

    public function processFile($file_name, $counter){
        //echo "\nProcessing order file: ".$file_name;
        $path = "/home/OrderManagement/testFiles/order_to_update";
        $full_name = $path."/".$file_name;
        //echo "\nProcessing order file (fullname): ".$full_name;

        //$full_name="/home/OrderManagement/testFiles/order_to_update/order_ov_it_DW_SG_20161017134001.xml";
        $processor = new OrderXMLAnalyzerUpdate($full_name);
        $processor->process();
        echo "\nOrder file: ".$file_name." PROCESSED (".$counter.")";


    }

}

//procedura import
$t = new OrderUpdater();
$t->start();
//$t->processFile("");