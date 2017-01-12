<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 19:47
 */

require_once realpath(dirname(__FILE__))."/FileManager.php";

class ROHInventoryFileManagerDM extends FileManager {

    public function getFilesToProcess($id_magazzino){
        $configManager = new ConfigManager();
        $dir = $configManager->getROHMAG1ExportInboundDir();
        $regExp = $configManager->getROHMAG1ExportInboundFileRexEx();

        if ($id_magazzino==2) {
            $dir = $configManager->getROHMAG2ExportInboundDir();
            $regExp = $configManager->getROHMAG2ExportInboundFileRexEx();
        }

            //echo "\ndir: $dir";
        //echo "\nregExp: $regExp";
        return $this->getFiles($dir, $regExp);
    }


        public function archiveFile($file_name){

    }

} 