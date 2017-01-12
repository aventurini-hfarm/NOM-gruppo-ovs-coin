<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 28/04/15
 * Time: 21:49
 */

require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/ConfigManagerDM.php";
require_once realpath(dirname(__FILE__))."/ShipmentXMLAnalyzerDM.php";
require_once realpath(dirname(__FILE__))."/../../common/ShipmentFileManagerDM.php";

ini_set('date.timezone', 'Europe/Rome');

class ShipmentImportDM {

    private $log;
    private $config;

    public function __construct()
    {
        $this->config = new ConfigManagerDM();
        $this->log = new KLogger('/var/log/nom/import_shipments.log',KLogger::DEBUG);

    }

    public function start($id_magazzino){
        $this->log->LogInfo("Start importing shipments");
        $fileManager = new ShipmentFileManagerDM();
        $lista_files = $fileManager->getFilesToProcess($id_magazzino);
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
        $this->log->LogInfo("Processing shipments file: ".$file_name);
        $path = $this->config->getShipmentExportInboundDir();
        $full_name = $path."/".$file_name;
        $this->log->LogDebug("Processing order file (fullname): ".$full_name);
        $processor = new ShipmentXMLAnalyzerDM($full_name);
        $processor->process();
        //muove il file nella cartella dei processati
        $dest_path = $this->config->getShipmentExportArchiveDir()."/".$file_name;

        $this->log->LogDebug("Copio: ".$full_name."-> ".$dest_path);
        if (copy($full_name, $dest_path)) {
            $this->log->LogDebug("Shipment file".$file_name." ARCHIVED");
            unlink($full_name);
        }

    }

    private function writePid() {

    }

}

$lock_file = fopen('/tmp/shipmentimportdm.pid', 'c');
$got_lock = flock($lock_file, LOCK_EX | LOCK_NB, $wouldblock);
if ($lock_file === false || (!$got_lock && !$wouldblock)) {

        die("Permission problem with pid");

}
else if (!$got_lock && $wouldblock) {
    die("Another instance is already running; terminating.\n");
}

// Lock acquired; let's write our PID to the lock file for the convenience
// of humans who may wish to terminate the script.
ftruncate($lock_file, 0);
fwrite($lock_file, getmypid() . "\n");

$t = new ShipmentImportDM();
$t->start(1);
$t->start(2);


// All done; we blank the PID file and explicitly release the lock
// (although this should be unnecessary) before terminating.
ftruncate($lock_file, 0);
flock($lock_file, LOCK_UN);