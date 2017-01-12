<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 05/05/15
 * Time: 08:24
 */
require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

require_once realpath(dirname(__FILE__))."/../common/OMDBManager.php";


class CleanMagentoDB {


    public function run()
    {
        $this->cleanLogs();
        $this->cleanSalesFlatInvoice();
        $this->cleanOrders();
        $this->cleanSalesFlatQuote();
        $this->cleanSalesOrderTax();
        $this->cleanSalesPaymentTransaction();
    }

    public function deleteBulkOrders($from, $to)
    {
        while ($from<=$to)
        {
            $this->deleteSalesFlatOrder($from);
            $from++;
        }
    }

    public function deleteSalesFlatOrder($id_ordine)
    {
        $con = OMDBManager::getMagentoConnection();
        $sql = "SELECT entity_id FROM sales_flat_order WHERE increment_id='$id_ordine'";
        $res = mysql_query($sql);
        $lista_entity_id= array();
        while ($row = mysql_fetch_object($res))
        {
            $lista_entity_id[]= $row->entity_id;
        }

        foreach ($lista_entity_id as $entity_id)
        {
            $sql ="DELETE FROM sales_flat_order_address WHERE  parent_id=$entity_id";
            $res = mysql_query($sql);

            $sql ="DELETE FROM sales_flat_order_grid WHERE entity_id=$entity_id";
            $res = mysql_query($sql);

            $sql ="DELETE FROM sales_flat_order_item WHERE order_id=$entity_id";
            $res = mysql_query($sql);

            $sql ="DELETE FROM sales_flat_order_payment WHERE parent_id=$entity_id";
            $res = mysql_query($sql);

            $sql ="DELETE FROM sales_flat_order_status_history WHERE  parent_id=$entity_id";
            $res = mysql_query($sql);

        }

        $sql ="DELETE FROM sales_flat_order WHERE  increment_id=$id_ordine";
        $res = mysql_query($sql);

        echo "\nFine";
    }

    public function deleteSalesFlatQuote($email_utente)
    {
        $con = OMDBManager::getMagentoConnection();
        $sql = "SELECT entity_id FROM sales_flat_quote WHERE customer_email='$email_utente'";
        $res = mysql_query($sql);
        $lista_entity_id= array();
        while ($row = mysql_fetch_object($res))
        {
            $lista_entity_id[]= $row->entity_id;
        }

        foreach ($lista_entity_id as $entity_id)
        {
            $sql ="DELETE FROM sales_flat_order_address WHERE  quote_id=$entity_id";
            $res = mysql_query($sql);

            $sql ="DELETE FROM sales_flat_quote_payment WHERE quote_id=$entity_id";
            $res = mysql_query($sql);

            $sql ="DELETE FROM sales_flat_quote_item_option o
			INNER JOIN sales_flat_quote_item i
			WHERE o.item_id=i.item_id
			AND i.quote_id = $entity_id";
            $res = mysql_query($sql);

            $sql ="DELETE FROM sales_flat_quote_item WHERE quote_id=$entity_id";
            $res = mysql_query($sql);

        }

        $sql ="DELETE FROM sales_flat_quote WHERE  customer_email=$email_utente";
        $res = mysql_query($sql);

        echo "\nFine";
    }

    public function cleanLogs() {
        $con = OMDBManager::getMagentoConnection();

        $sql = "DELETE FROM dataflow_batch_export";
        $res = mysql_query($sql);
        Mage::log("dataflow_batch_export ok");

        $sql = "DELETE FROM dataflow_batch_import";
        $res = mysql_query($sql);
        Mage::log("dataflow_batch_import ok");

        $sql = "DELETE FROM log_customer";
        $res = mysql_query($sql);
        Mage::log("log_customer ok");

        $sql = "DELETE FROM log_quote";
        $res = mysql_query($sql);
        Mage::log("log_quote ok");

        $sql = "DELETE FROM log_summary";
        $res = mysql_query($sql);
        Mage::log("log_summary ok");

        $sql = "DELETE FROM log_summary_type";
        $res = mysql_query($sql);
        Mage::log("log_summary_type ok");

        $sql = "DELETE FROM log_url";
        $res = mysql_query($sql);
        Mage::log("log_url ok");

        $sql = "DELETE FROM log_url_info";
        $res = mysql_query($sql);
        Mage::log("log_url_info ok");

        $sql = "DELETE FROM log_visitor";
        $res = mysql_query($sql);
        Mage::log("log_visitor ok");

        $sql = "DELETE FROM log_visitor_info";
        $res = mysql_query($sql);
        Mage::log("log_visitor_info ok");

        $sql = "DELETE FROM log_visitor_online";
        $res = mysql_query($sql);
        Mage::log("log_visitor_online ok");

        $sql = "DELETE FROM report_viewed_product_index";
        $res = mysql_query($sql);
        Mage::log("report_viewed_product_index ok");

        $sql = "DELETE FROM report_viewed_product_aggregated_daily";
        $res = mysql_query($sql);
        Mage::log("report_viewed_product_aggregated_daily ok");

        $sql = "DELETE FROM report_viewed_product_aggregated_monthly";
        $res = mysql_query($sql);
        Mage::log("report_viewed_product_aggregated_monthly ok");

        $sql = "DELETE FROM report_viewed_product_aggregated_yearly";
        $res = mysql_query($sql);
        Mage::log("report_viewed_product_aggregated_yearly ok");

        $sql = "DELETE FROM report_compared_product_index";
        $res = mysql_query($sql);
        Mage::log("report_compared_product_index ok");

        $sql = "DELETE FROM report_event";
        $res = mysql_query($sql);
        Mage::log("report_event ok");

        OMDBManager::closeConnection($con);

    }

    public function cleanSalesFlatInvoice() {
        $con = OMDBManager::getMagentoConnection();

        $sql = "DELETE FROM sales_flat_invoice";
        $res = mysql_query($sql);
        Mage::log("sales_flat_invoice ok");

        $sql = "DELETE FROM sales_flat_invoice_grid";
        $res = mysql_query($sql);
        Mage::log("sales_flat_invoice_grid ok");

        $sql = "DELETE FROM sales_flat_invoice_item";
        $res = mysql_query($sql);
        Mage::log("sales_flat_invoice_item ok");
        OMDBManager::closeConnection($con);
    }

    public function cleanOrders() {
        $con = OMDBManager::getMagentoConnection();

        $sql = "DELETE FROM sales_flat_order";
        $res = mysql_query($sql);
        Mage::log("sales_flat_order ok");

        $sql = "DELETE FROM sales_flat_order_address";
        $res = mysql_query($sql);
        Mage::log("sales_flat_order_address ok");

        $sql = "DELETE FROM sales_flat_order_grid";
        $res = mysql_query($sql);
        Mage::log("sales_flat_order_grid ok");

        $sql = "DELETE FROM sales_flat_order_item";
        $res = mysql_query($sql);
        Mage::log("sales_flat_order_item ok");

        $sql = "DELETE FROM sales_flat_order_payment";
        $res = mysql_query($sql);
        Mage::log("sales_flat_order_payment ok");

        $sql = "DELETE FROM sales_flat_order_status_history";
        $res = mysql_query($sql);
        Mage::log("sales_flat_order_status_history ok");


        OMDBManager::closeConnection($con);
    }


    public function cleanSalesFlatQuote() {
        $con = OMDBManager::getMagentoConnection();

        $sql = "DELETE FROM sales_flat_quote";
        $res = mysql_query($sql);
        Mage::log("sales_flat_quote ok");

        $sql = "DELETE FROM sales_flat_quote_address";
        $res = mysql_query($sql);
        Mage::log("sales_flat_quote_address ok");

        $sql = "DELETE FROM sales_flat_quote_address_item";
        $res = mysql_query($sql);
        Mage::log("sales_flat_quote_address_item ok");

        $sql = "DELETE FROM sales_flat_quote_item";
        $res = mysql_query($sql);
        Mage::log("sales_flat_quote_item ok");

        $sql = "DELETE FROM sales_flat_quote_payment";
        $res = mysql_query($sql);
        Mage::log("sales_flat_quote_payment ok");

        $sql = "DELETE FROM sales_flat_quote_shipping_rate";
        $res = mysql_query($sql);
        Mage::log("sales_flat_quote_shipping_rate ok");

        $sql = "DELETE FROM sales_flat_quote_shipping_rate";
        $res = mysql_query($sql);
        Mage::log("sales_flat_quote_shipping_rate ok");


        OMDBManager::closeConnection($con);
    }

    public function cleanSalesOrderTax() {
        $con = OMDBManager::getMagentoConnection();

        $sql = "DELETE FROM sales_order_tax";
        $res = mysql_query($sql);
        Mage::log("sales_order_tax ok");

        $sql = "DELETE FROM sales_order_tax_item";
        $res = mysql_query($sql);
        Mage::log("sales_order_tax_item ok");

        OMDBManager::closeConnection($con);
    }

    public function cleanSalesPaymentTransaction() {
        $con = OMDBManager::getMagentoConnection();

        $sql = "DELETE FROM sales_payment_transaction";
        $res = mysql_query($sql);
        Mage::log("sales_payment_transaction ok");

        OMDBManager::closeConnection($con);
    }

    public function cleanSupportDB() {
        $con = OMDBManager::getConnection();
        $sql ="DELETE FROM delivery";
        $res = mysql_query($sql);

        $sql ="DELETE FROM option_lineitems";
        $res = mysql_query($sql);

        $sql ="DELETE FROM order_custom_attributes";
        $res = mysql_query($sql);

        $sql ="DELETE FROM payments";
        $res = mysql_query($sql);

        $sql ="DELETE FROM promotions";
        $res = mysql_query($sql);

        $sql ="DELETE FROM shipment";
        $res = mysql_query($sql);

        $sql ="DELETE FROM shipment_line";
        $res = mysql_query($sql);

        OMDBManager::closeConnection($con);


    }



}

$t = new CleanMagentoDB();
$t->cleanOrders();
$t->cleanSupportDB();

//$shell->run();
//$shell->deleteSalesFlatOrder('12200000006');
//$shell->deleteSalesFlatQuote('cnee05900c@istruzione.it');
//$shell->deleteBulkOrders(100000000 , 100000048);
