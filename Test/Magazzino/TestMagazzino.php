<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 31/07/15
 * Time: 11:10
 */
require_once "/home/OrderManagement/omdb/OrderDBHelper.php";

class TestMagazzino {

    public function run() {
        $con =  OMDBManager::getConnection();

        $sql ="SELECT * FROM delivery WHERE delivery_id>500000";
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $obj = new stdClass();
            $obj->delivery_id = $row->delivery_id;
            $obj->order_number = $row->order_number;
            $lista[] = $obj;
        }

        OMDBManager::closeConnection($con);
        echo "\nLISTA DELIVERY\n";
        print_r($lista);
        $this->getListaOrdiniConMiltiItem($lista);
    }

    public function getListaOrdiniConMiltiItem($lista) {
        $con =  OMDBManager::getMagentoConnection();
        echo "\nLISTA ORDINI\n";
        $lista_ordini = array();
        foreach ($lista as $obj) {
            $order_number = $obj->order_number;
            $sql ="SELECT * from sales_flat_order WHERE dw_order_number='$order_number' AND total_item_count>1 ";
            $res = mysql_query($sql);
            while ($row=mysql_fetch_object($res)) {
                $objOrd = new stdClass();
                $objOrd->increment_id = $row->increment_id;
                $objOrd->dw_order_number = $row->dw_order_number;
                $objOrd->customer_email = $row->customer_email;
                $objOrd->created_at = $row->created_at;
                $objOrd->delivery_id = $obj->delivery_id;
                $objOrd->total_item_count = $row->total_item_count;
                $objOrd->status = $row->status;
                $objOrd->dw_order_datetime = $row->dw_order_datetime;

                if ($objOrd->status=='processing') {
                    $lista_ordini[] = $objOrd;
                }
            }
        }
        OMDBManager::closeConnection($con);
        $header=  "\nIncrement_id, order_number, email, created_at, delivery_id, total_item_count, status, dw_order_date";
        $handle = fopen("lista.csv",  'w');
        fwrite($handle, $header);


        foreach ($lista_ordini as $record) {
            $riga= "\n$record->increment_id, $record->dw_order_number, $record->customer_email, $record->created_at, $record->delivery_id, $record->total_item_count, $record->status, $record->dw_order_datetime";
            echo "\n$riga";
            fwrite($handle, $riga);
        }

        fclose($handle);
    }

}

$t = new TestMagazzino();
$t->run();