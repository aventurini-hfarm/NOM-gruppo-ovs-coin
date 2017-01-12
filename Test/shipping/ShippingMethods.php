<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 03/05/15
 * Time: 10:27
 */

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class ShippingMethods {

    public function getAllShippingMethods()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

        $options = array();

        foreach($methods as $_ccode => $_carrier)
        {
            $_methodOptions = array();
            if($_methods = $_carrier->getAllowedMethods())
            {
                foreach($_methods as $_mcode => $_method)
                {
                    $_code = $_ccode . '_' . $_mcode;
                    $_methodOptions[] = array('value' => $_code, 'label' => $_method);
                }

                if(!$_title = Mage::getStoreConfig("carriers/$_ccode/title"))
                    $_title = $_ccode;

                $options[] = array('value' => $_methodOptions, 'label' => $_title);
            }
        }

        print_R($options);
        return $options;
    }
}

$t = new ShippingMethods();
$t->getAllShippingMethods();