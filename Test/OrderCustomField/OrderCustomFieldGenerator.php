<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 27/04/15
 * Time: 22:12
 */

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class OrderCustomFieldGenerator {


    public function start(){

        //  customer-locale
        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Customer Locale',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'customer_locale', $attribute);
        $installer->addAttribute('quote', 'customer_locale', $attribute);




        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'DW Order',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'dw_order_number', $attribute);
        $installer->addAttribute('quote', 'dw_order_number', $attribute);

        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'DW Order Date Time',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'dw_order_datetime', $attribute);
        $installer->addAttribute('quote', 'dw_order_datetime', $attribute);


        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Need Invoice',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'needInvoice', $attribute);
        $installer->addAttribute('quote', 'needInvoice', $attribute);

        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Codice Fiscale',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'codice_fiscale', $attribute);
        $installer->addAttribute('quote', 'codice_fiscale', $attribute);

        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Numero Colli',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'ncolli', $attribute);
        $installer->addAttribute('quote', 'ncolli', $attribute);

        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Lettera Vettura',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'lettera_vettura', $attribute);
        $installer->addAttribute('quote', 'lettera_vettura', $attribute);

        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'First Track',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'first_track', $attribute);
        $installer->addAttribute('quote', 'first_track', $attribute);


        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Shipping date',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'shipping_date', $attribute);
        $installer->addAttribute('quote', 'shipping_date', $attribute);


        /**
         * Questo lo uso come un contatore
         * per capire se l'ordine è stato splittato in n parti
         * man mano che ricevo il file di shipment vado a vedere il contatore
         * se il contatore == 0 significa che ho ricevuto tutti i file di shipment e posso chiudere l'ordine
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Split Delivery',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'split_delivery', $attribute);
        $installer->addAttribute('quote', 'split_delivery', $attribute);


        /**
         * parte relativa alle loyalty card passate nell'ordine
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'loyalty Card',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'loyaltyCard', $attribute);
        $installer->addAttribute('quote', 'loyaltyCard', $attribute);


        /**
         * parte relativa alle loyalty card passate nell'ordine
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Reward Points',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'rewardPoints', $attribute);
        $installer->addAttribute('quote', 'rewardPoints', $attribute);


        /**
         * contiene l'identificato dell'id utente DW
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'DW Customer ID',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'dw_customer_id', $attribute);
        $installer->addAttribute('quote', 'dw_customer_id', $attribute);


        /**
         * attributi custom singoli item ordine
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'DW Promo ID',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order_item', 'item_dw_promo_id', $attribute);
        $installer->addAttribute('quote_item', 'item_dw_promo_id', $attribute);

        /**
         * attributi custom singoli item ordine
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Punti Extra',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order_item', 'item_dw_extra_points', $attribute);
        $installer->addAttribute('quote_item', 'item_dw_extra_points', $attribute);

        /**
         * attributi custom singoli item ordine
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Punti Resi',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order_item', 'item_dw_return_points', $attribute);
        $installer->addAttribute('quote_item', 'item_dw_return_points', $attribute);

        /**
         * attributi custom singoli item: ordine sconto originale
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Sconto Originale',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order_item', 'original_discount', $attribute);
        $installer->addAttribute('quote_item', 'original_discount', $attribute);

        /**
         * attributi custom singoli item ordine
         */
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'int',
            'backend_type'  => 'int',
            'frontend_input' => 'boolean',
            'is_user_defined' => true,
            'label'         => 'Has Options',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => '0'
        );
        $installer->addAttribute('order_item', 'item_has_options', $attribute);
        $installer->addAttribute('quote_item', 'item_has_options', $attribute);

        //Numero Scontrino
        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Numero Scontrino',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'bill_number', $attribute);
        $installer->addAttribute('quote', 'bill_number', $attribute);
        $installer->addAttribute('creditmemo', 'bill_number', $attribute);

        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Data Scontrino',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'bill_date', $attribute);
        $installer->addAttribute('quote', 'bill_date', $attribute);
        $installer->addAttribute('creditmemo', 'bill_date', $attribute);

        //Numero Fattura
        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Numero Fattura',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'invoice_number', $attribute);
        $installer->addAttribute('quote', 'invoice_number', $attribute);
        $installer->addAttribute('creditmemo', 'invoice_number', $attribute);

        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
        $installer = new Mage_Sales_Model_Mysql4_Setup;
        $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Data Fattura',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
        );
        $installer->addAttribute('order', 'invoice_date', $attribute);
        $installer->addAttribute('quote', 'invoice_date', $attribute);
        $installer->addAttribute('creditmemo', 'invoice_date', $attribute);

        //complete setup
        $installer->endSetup();
    }
}


$t = new OrderCustomFieldGenerator();
$t->start();