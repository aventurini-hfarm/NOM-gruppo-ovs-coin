<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 25/10/16
 * Time: 20:28
 */
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

require_once realpath(dirname(__FILE__))."/../common/OMDBManager.php";

class FixNoteCredito {

    private $con;

    public function process($lista) {
        $this->con = OMDBManager::getMagentoConnection();
        foreach ($lista as $creditmemo_id) {
            $this->fixNotaDiCredito($creditmemo_id);
        }
        OMDBManager::closeConnection($this->con);
    }

    public function fixNotaDiCredito($creditmemo_id)
    {

        //echo "\nConnessione";

        $sql = "UPDATE sales_flat_creditmemo_item i, sales_flat_order_item o
set
i.base_price = o.base_price,
i.tax_amount = 0,
i.base_row_total = o.base_row_total,
i.discount_amount = o.discount_amount,
i.row_total = o.row_total,
i.price_incl_tax = o.price_incl_tax,
i.base_tax_amount =0,
i.base_price_incl_tax = o.base_price_incl_tax,
i.price = o.price,
i.base_row_total_incl_tax = o.base_row_total_incl_tax,
i.row_total_incl_tax = o.row_total_incl_tax
where
i.order_item_id = o.item_id
and
i.parent_id=$creditmemo_id";
        //echo "\nSQL:".$sql;
        $res = mysql_query($sql, $this->con);

        $sql = "SELECT * FROM sales_flat_creditmemo WHERE entity_id= $creditmemo_id";
        $res = mysql_query($sql, $this->con);
        $discount_amount = 0;
        $shipping_amount = 0;
        while ($row = mysql_fetch_object($res)) {
            $discount_amount = $row->discount_amount;
            $shipping_amount = $row->$shipping_amount;

        }

        //ricalcola il totale
        $sql = "SELECT * FROM `sales_flat_creditmemo_item` where parent_id=$creditmemo_id";
        $res = mysql_query($sql, $this->con);
        $totale_nota_credito = 0;
        while ($row = mysql_fetch_object($res)) {
            $totale_riga = $row->row_total;
            $sconto_riga = $row->discount_amount;
            $totale_nota_credito += ($totale_riga - $sconto_riga);
        }


        echo "\nSubtotal: ".$totale_nota_credito;
        $totale = $totale_nota_credito - $discount_amount + $shipping_amount;
        echo "\nTotale: ".$totale;

        $sql = "UPDATE sales_flat_creditmemo
         set grand_total = $totale,
         base_subtotal_incl_tax = $totale_nota_credito,
         subtotal_incl_tax = $totale_nota_credito,
         base_subtotal = $totale_nota_credito,
         base_subtotal = $totale_nota_credito,
         subtotal = $totale_nota_credito,
         base_grand_total = $totale,
         base_grand_total = $totale
        WHERE entity_id = $creditmemo_id";
        echo "\nSQL: ".$sql;
        $res = mysql_query($sql, $this->con);
        echo "\nNC aggiornata\n";
        }

}


$lista = array(702,
    709,
    710,
    723,
    724,
    725,
    726,
    727,
    728,
    729,
    730,
    731,
    732,
    733,
    734,
    735,
    736,
    737,
    738,
    739,
    740,
    741,
    742,
    743,
    744,
    745,
    746,
    747,
    748,
    749,
    750,
    751,
    752,
    753,
    754,
    755,
    756,
    757,
    758,
    760,
    763,
    764,
    765,
    766,
    767,
    768,
    769,
    773,
    774,
    776,
    777,
    778,
    780,
    781,
    782,
    783,
    784,
    786,
    787,
    788,
    789);

//$lista = array(array('00294061 ','00037291'));

$t = new FixNoteCredito();
$t->process($lista);
//$t->processFixPadding();
