<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 19:47
 */

require_once realpath(dirname(__FILE__))."/FileManager.php";

class RegisteredFileManager extends FileManager {

    public function getFilesToProcess(){
        $configManager = new ConfigManager();
        $dir = $configManager->getCustomerExportInboundDir();
        $regExp = $configManager->getRegisteredExportInboundFileRexEx();
        //echo "\ndir: $dir";
        echo "\nregExp: $regExp";
        return $this->getFiles($dir, $regExp);
    }

    public function archiveFile($file_name){

    }

} 