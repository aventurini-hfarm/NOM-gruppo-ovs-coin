<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 20:16
 */


require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');

require_once realpath(dirname(__FILE__))."/../customers/MagentoCustomerHelper.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/CountersHelper.php";
require_once realpath(dirname(__FILE__))."/../../common/MagentoHelper.php";
require_once realpath(dirname(__FILE__))."/../../omdb/OrderDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../omdb/CountryDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../common/OMDBManager.php";

Mage::app();


class MagentoOrderHelperUpdater {


    const CUSTOMER_RANDOM = null;

    //protected $_shippingMethod = 'freeshipping_freeshipping';
    protected $_shippingMethod = 'flatrate_flatrate';
    protected $_shippingDescription = "";
    protected $_paymentMethod = 'cashondelivery';

    protected $_customer = null;

    protected $_subTotal = 0;

    protected $_order;
    protected $_storeId;

    protected $dw_order;

    private $log;
    private $lista_ordini_processati;

    public function __construct(){

        $this->log = new KLogger('/var/log/nom/magento_order_helper.log',KLogger::DEBUG);
        $this->lista_ordini_processati = array();
    }


    public function getOrderIdByDWId($dw_id) {
        $data = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToSelect('increment_id')
            ->addAttributeToFilter('dw_order_number',$dw_id)->load()->getData();

        $errors = array_filter($data);

        if (empty($errors)) {
            return null; //id non trovato
        }

        if (is_array($data)) {

            return $data[0]['increment_id'];
        } else
            return $data['increment_id'];

    }



    /*Metodo usato per aggiornare gli ordini caricati con la procedura di RINO e che devono essere aggiornati con la nuova di VS*/
    public function  updateOrder(OrderObject $dw_order) {
        //echo "\nUPDATE ORDER: ".$dw_order->order_no;

        $magOrderHelper = new MagentoOrderHelper();

        $increment_id = $magOrderHelper->getOrderIdByDWId($dw_order->order_no);

        if (!$increment_id) {
            echo "\nOrdine non trovato: " . $dw_order->order_no;
            return;
        }
        $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);

/*
        if ($order->status != 'processing') {
            echo "\nORdine scartato perchè non in processing: ".$dw_order->order_no."\n";
            return;
        }
*/
/*

        if ($order->bill_date!="20/10/2016") {
            echo "\nORdine scartato perchè non di interesse: ".$dw_order->order_no.", ". $order->bill_number.",  bill_date:". $order->bill_date."\n";
            return;
        }
*/


        $ordini_da_aggiornare = array("280232",
            "280817",
            "282636",
            "283909",
            "286139",
            "286017",
            "289034");



        //$ordini_da_aggiornare = array('00289503');
        //$ordini_da_aggiornare = array();



        if ( !in_array($order->dw_order_number, $ordini_da_aggiornare)) {
            //echo "\nORdine scartato perchè non di interesse: ".$dw_order->order_no."\n";
            return;
        }
        $this->lista_ordini_processati[] = $order->dw_order_number;
        //In realtà lo shipping facendo così non viene sommato ma solo presente in ordine
        $shippingPrice = $dw_order->totals->adjusted_shipping_total['gross-price'];
        $order->setBaseShippingAmount($shippingPrice);
        $order->setShippingAmount($shippingPrice);

        //calcola sconti

        $merchandize_total = $dw_order->totals->merchandize_total;
        $value = 0;
        $description = array();
        //echo "\nDUMP Merchandize_total\n";
        foreach ($merchandize_total as $item) {
            //print_r($item);
            if ($item['promotion-id']) {
                //inserisce i dati addizionali sul DB
                $value += ($item['discount-gross-price'] * -1);
                $description[] = $item['promotion-id'];
            }
        }
        //echo "\nMerchandize Promo: \n";
        //print_r($description);
        $order->setBaseDiscountAmount($value);
        $order->setDiscountAmount($value);
        $order->setDiscountDescription(implode("|", $description));

        $entity_id=$order->entity_id;


        //Custruisce la lista products

        $customPrice = 0;

        $lista_item = $dw_order->lista_prodotti;
        $con = OMDBManager::getMagentoConnection();
        $numero_items = 0;

        //controlla se numero righe coincide;

        foreach ($lista_item as $item) {
            $numero_items++;
        }
        //controllo numero_items;
        $sql ="SELECT count(*) as numero_righe from sales_flat_order_item WHERE order_id=$entity_id ";
        $res = mysql_query($sql);
        $numero_righe_magento = 0;
        while ($row=mysql_fetch_object($res)) {
            $numero_righe_magento = $row->numero_righe;
        }

        //echo "\nNumero Righe magento: ".$numero_righe_magento." , righe_dw: ".$numero_items;
        $righe_modificate = false;
        if ($numero_righe_magento != $numero_items) {
            echo "\nOrdine : ".$dw_order->order_no." ha numero righe diverso: ";
            $righe_modificate = true;
           // return;
        }


            foreach ($lista_item as $item) {
            $numero_items++;
            $sku = $item->product_id;

            $sql ="SELECT * from sales_flat_order_item WHERE order_id=$entity_id AND sku='$sku'";
            $res = mysql_query($sql);
            $qty_ordered = 0;
            while ($row=mysql_fetch_object($res)) {
                $qty_ordered = $row->qty_ordered;
                if ($qty_ordered != $item->quantity) {
                    echo "\nOrdine : ".$dw_order->order_no." ha quantità modificate per sku: ".$sku;
                    return;
                }
            }
            //echo "\nQty_ordered: ".$qty_ordered." , qty_dw: ".$item->quantity;



            $customPrice = $item->base_price;
            $rowTotal = $item->gross_price;
            $rowDiscount = -1 * (float)$item->discount_gross_price; //indica lo sconto complessivo (ovvero già moltiplicato per le qta)
            if ($rowDiscount<=0) $rowDiscount = 0;
            $rowPromotionId = $item->promotion_id;
            $rowCampaignId = $item->campaign_id;
            $hasOptions = $item->has_options;
            $rowTax = $item->tax;
            $sql = "UPDATE sales_flat_order_item  SET price=$customPrice,
            base_price=$customPrice, original_price=$customPrice, row_total=$rowTotal, tax_amount=0,
            base_tax_amount=0, tax_invoiced=0, base_tax_invoiced=0, discount_invoiced = $rowDiscount, base_discount_invoiced = $rowDiscount,
            row_invoiced=$rowTotal, base_row_invoiced=$rowTotal,price_incl_tax=$rowTotal,base_price_incl_tax=$rowTotal,row_total_incl_tax=$rowTotal,
            base_row_total=$rowTotal, discount_amount=$rowDiscount, base_discount_amount=$rowDiscount,
            base_original_price=$customPrice
            WHERE order_id=$entity_id AND sku='$sku'
            ";
            //echo "\nSQL: ".$sql;
            $res = mysql_query($sql);
        }

        $_subTotal = $dw_order->totals->order_total['gross-price'];                      // RINO 30/07/2016
        $iva = $dw_order->totals->order_total['tax'];

        if ($righe_modificate) {
            //aggiorna adesso i totali reali:
            $sql ="SELECT * from sales_flat_order_item WHERE order_id=$entity_id";
            $res = mysql_query($sql);
            $totale = 0;
            while ($row=mysql_fetch_object($res)) {
                $totale += ($row->row_total - $row->discount_amount);
                //echo "\nTotale : ".$totale;
            }

            //echo "\nShippingPrice: ".$shippingPrice.", discount: ".$order->getShippingDiscountAmount();
            $totale += $shippingPrice;
            //echo "\nTotale con Shipping: ".$totale;
            $totale = $totale - $order->getDiscountAmount();
            //echo "\nDiscount: ".$order->getDiscountAmount();
            $_subTotal = $totale;
            //echo "\nSubTotale: ".$_subTotal;
        }


        OMDBManager::closeConnection($con);




        $order->setSubtotal($_subTotal)
            ->setBaseSubtotal($_subTotal)
            ->setGrandTotal($_subTotal)
            ->setBaseGrandTotal($_subTotal);




        $imponibile = $dw_order->totals->order_total['net-price'];

        $order->setTaxAmount($iva);
        $order->setBaseTaxAmount($imponibile);






        $order->save();
        echo "\nAggiornato ordine: ".$order->dw_order_number;
        $this->setStatusCompleteByDwOrderNumber($order->dw_order_number);

    }


    public function setStatusCompleteByDwOrderNumber($dw_order_number) {

        $con = OMDBManager::getMagentoConnection();
        $sql ="UPDATE sales_flat_order SET status='complete', state='complete' WHERE dw_order_number='$dw_order_number'";
        echo "\nSQL: ".$sql;
        $res = mysql_query($sql);
        OMDBManager::closeConnection($con);

    }
}

//echo "\nPRova";
//$t = new MagentoOrderHelper();
//echo "\nStep 2";
//$dw_customer = new stdClass();
//$dw_customer->customer_no = "00687834";
//$t->setCustomerDWCode($dw_customer,null);
