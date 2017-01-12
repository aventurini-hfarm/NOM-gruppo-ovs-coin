<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 21/10/16
 * Time: 21:19
 */

class GrepUtils {



    public function process($lista_ordini) {
        $lista_files = array();
        foreach ($lista_ordini as $ordine) {
            $nome_file = $this->getFile($ordine);
            array_push($lista_files, $nome_file);
        }

        echo "\nDUMP FILES";
        $unique = array_unique($lista_files);
        print_r($unique);
        $this->copia($unique);
    }

    public function copia($lista_files) {
        foreach ($lista_files as $file) {
            echo "\nCopia: file: ".basename($file);
            if (!copy($file, '/home/OrderManagement/testFiles/order_to_update/'.basename($file))) {
                echo "\nErrore durante la copia";
            }
        }

    }

    public function getFile($order_number) {
        $command = "grep -r \"".$order_number."\" /home/OrderManagement/testFiles/order_export/archive";
        $output = array();
        exec($command, $output);
        foreach($output as $line) {
            $out = explode(":",$line);
            echo "\nFile: ".$out[0]." ordine: ".$order_number;
            return $out[0];
        }
    }

}

$lista_ordini= array("280232",
"280817",
"282636",
"283909",
"286139",
"286017",
"289034");
$t = new GrepUtils();
$t->process($lista_ordini);