<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 19:22
 */

class FileManager {


    public function getFiles($directory, $reg_exp)
    {
        $iterator = new FilesystemIterator($directory);

        $filter = new RegexIterator($iterator, $reg_exp);
        //$iterator = new FilesystemIterator("/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/customer_export/inbound/");
        //$filter = new RegexIterator($iterator, '/^.*-customer_[a-zA-Z]{2}_[a-zA-Z]{2}_DW_SG_[0-9]{14}.xml$/i');

        $filelist = array();
        foreach($filter as $entry) {
            $filelist[] = $entry->getFilename();
        }


        return $filelist;
    }

    public function getFiles3(){
        $iterator = new FilesystemIterator(".");
        $filelist = array();
        foreach($iterator as $entry) {
                $filelist[] = $entry->getFilename();
        }
        print_r($filelist);
    }

    public function getFiles2(){
        $iterator = new FilesystemIterator("/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/customer_export/inbound/");
        $filter = new RegexIterator($iterator, '/^.*\.(php)$/i');
        $filter = new RegexIterator($iterator, '/^.*-customer_[a-zA-Z]{2}_[a-zA-Z]{2}_DW_SG_[0-9]{14}.xml$/i');

        $filelist = array();
        foreach($filter as $entry) {
            $filelist[] = $entry->getFilename();
        }

        print_r($filelist);
    }
}


//$t = new FileManager();
//$t->getFiles2();