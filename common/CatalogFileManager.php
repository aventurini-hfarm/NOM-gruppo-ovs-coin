<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 19:47
 */

require_once realpath(dirname(__FILE__))."/FileManager.php";

class CatalogFileManager extends FileManager {

    public function getFilesToProcess(){
        $configManager = new ConfigManager();
        $dir = $configManager->getCatalogExportInboundDir();
        $regExp = $configManager->getCatalogExportInboundFileRexEx();
        //echo "\ndir: $dir";
        //echo "\nregExp: $regExp";
        return $this->getFiles($dir, $regExp);
    }

    public function archiveFile($file_name){

    }

} 