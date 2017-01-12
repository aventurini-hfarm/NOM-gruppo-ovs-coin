<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 19:47
 */

require_once realpath(dirname(__FILE__))."/FileManager.php";

class ShipmentFileManagerDM extends FileManager {

    public function getFilesToProcess($id_magazzino){
        $configManager = new ConfigManagerDM();
        if ($id_magazzino==2) {
            $dir = $configManager->getShipmentExportInboundDir();
            $regExp = $configManager->getShipmentMAG2ExportInboundFileRexEx();
        } else {
            $dir = $configManager->getShipmentExportInboundDir();
            $regExp = $configManager->getShipmentMAG1ExportInboundFileRexEx();

        }

        //echo "\ndir: $dir";
        //echo "\nregExp: $regExp";
        return $this->getFiles($dir, $regExp);
    }

    public function archiveFile($file_name){

    }

} 