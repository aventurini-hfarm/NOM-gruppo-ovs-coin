<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 19:47
 */

require_once realpath(dirname(__FILE__))."/FileManager.php";

class QOHInventoryFileManagerDM extends FileManager {

    public function getFilesToProcess($id_magazzino){
        $configManager = new ConfigManagerDM();
        if ($id_magazzino==2) {
            $dir = $configManager->getQOHMAG2ExportInboundDir();
            $regExp = $configManager->getQOHMAG2ExportInboundFileRexEx();
        } else {
            $dir = $configManager->getQOHMAG1ExportInboundDir();
            $regExp = $configManager->getQOHMAG1ExportInboundFileRexEx();

        }

        //echo "\ndir: $dir";
        //echo "\nregExp: $regExp";
        return $this->getFiles($dir, $regExp);
    }

    public function archiveFile($file_name){

    }

} 