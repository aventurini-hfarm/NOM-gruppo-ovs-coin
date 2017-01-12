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
        Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));
            $installer = new Mage_Sales_Model_Mysql4_Setup;
            $attribute  = array(
            'type'          => 'varchar',
            'backend_type'  => 'varchar',
            'frontend_input' => 'varchar',
            'is_user_defined' => true,
            'label'         => 'Reward Points Balance',
            'visible'       => true,
            'required'      => false,
            'user_defined'  => false,
            'searchable'    => false,
            'filterable'    => false,
            'comparable'    => false,
            'default'       => ''
            );
            $installer->addAttribute('creditmemo', 'reward_points_balance', $attribute);



        //complete setup
        $installer->endSetup();
    }
}


$t = new OrderCustomFieldGenerator();
$t->start();