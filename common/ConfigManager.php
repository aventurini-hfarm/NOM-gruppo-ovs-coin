<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 04/02/15
 * Time: 10:11
 */

class ConfigManager {

    private $ini_array = array();

    public function __construct()
    {
        $this->ini_array = parse_ini_file(realpath(dirname(__FILE__))."/../config/config.ini");

    }

    public  function getProperty($property_name){
        return $this->ini_array[$property_name];
    }

    public function getCustomerExportInboundDir(){
        return $this->getProperty('customer_export.inbound.dir');
    }

    public function getCustomerExportArchiveDir(){
        return $this->getProperty('customer_export.archive.dir');
    }

    public function getCustomerExportInboundFileRexEx(){
        return $this->getProperty('customer_export.inbound.file_regex');
    }

    public function getRegisteredExportInboundFileRexEx(){
        return $this->getProperty('registered_export.inbound.file_regex');
    }

    public function getCatalogExportInboundDir(){
        return $this->getProperty('catalog_export.inbound.dir');
    }

    public function getCatalogExportInboundFileRexEx(){
        return $this->getProperty('catalog_export.inbound.file_regex');
    }

    public function getCatalogExportArchiveDir(){
        return $this->getProperty('catalog_export.archive.dir');
    }

    public function getOrderExportInboundDir(){
        return $this->getProperty('order_export.inbound.dir');
    }

    public function getOrderExportInboundFileRexEx(){
        return $this->getProperty('order_export.inbound.file_regex');
    }

    public function getOrderExportArchiveDir(){
        return $this->getProperty('order_export.archive.dir');
    }

    public function getShipmentExportInboundDir(){
        return $this->getProperty('shipment_export.inbound.dir');
    }

    public function getShipmentExportInboundFileRexEx(){
        return $this->getProperty('shipment_export.inbound.file_regex');
    }

    public function getShipmentExportArchiveDir(){
        return $this->getProperty('shipment_export.archive.dir');
    }

    public function getDeliveryExportOutboundDir(){
        return $this->getProperty('delivery_export.outbound.dir');
    }


    public function getQOHExportInboundDir(){
        return $this->getProperty('qoh_export.inbound.dir');
    }

    public function getQOHExportInboundFileRexEx(){
        return $this->getProperty('qoh_export.inbound.file_regex');
    }

    public function getQOHExportArchiveDir(){
        return $this->getProperty('qoh_export.inbound.archive.dir');
    }

    public function getROHExportInboundDir(){
        return $this->getProperty('roh_export.inbound.dir');
    }

    public function getROHExportInboundFileRexEx(){
        return $this->getProperty('roh_export.inbound.file_regex');
    }

    public function getROHExportArchiveDir(){
        return $this->getProperty('roh_export.inbound.archive.dir');
    }

    public function getEcommerceShopCode(){
        return $this->getProperty('ecommerce_shop.code');
    }

    public function getEcommerceShopCodeIt(){
        return $this->getProperty('ecommerce_shop.code.it');
    }
    public function getEcommerceShopCodeEs(){
        return $this->getProperty('ecommerce_shop.code.es');
    }

    public function getEcommerceShopCodiceCassa(){
        return $this->getProperty('ecommerce_shop.codice_cassa');
    }

    public function getBillExportOutboundDir(){
        return $this->getProperty('bill_export.outbound.dir');
    }

    public function getInvoiceExportOutboundDir(){
        return $this->getProperty('invoice_export.outbound.dir');
    }

    public function getStockExportOutboundDir(){
        return $this->getProperty('stock_export.outbound.dir');
    }

    public function getStockExportArchiveDir(){
        return $this->getProperty('stock_export.archive.dir');
    }


    public function getDeliveryImportInboundDir(){
        return $this->getProperty('delivery_import.inbound.dir');
    }

    public function getDeliveryImportInboundFileRexEx(){
        return $this->getProperty('delivery_import.inbound.file_regex');
    }

    public function getDeliveryImportArchiveDir(){
        return $this->getProperty('delivery_import.archive.dir');
    }

    public function getCountryStoreRexEx() {
        return $this->getProperty('country_store');
    }

    public function getHost() {
        return $this->getProperty('host');
    }

    /**
     * Metodo usato nel flusso scontrini per fornire un userid fake in caso di utente guest
     * @return mixed
     */
    public function getFakeUserId($country) {
        if (strtoupper($country)=='IT')
            return $this->getProperty('fake_userid.italia');
        else
            return $this->getProperty('fake_userid.estero');
    }
}



