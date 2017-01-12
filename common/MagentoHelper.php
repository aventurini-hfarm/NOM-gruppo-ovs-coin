<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 05/05/15
 * Time: 08:58
 */

//require_once realpath(dirname(__FILE__))."/ConfigManager.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');
Mage::app();

class MagentoHelper {



    public function getStoreIdFromFile($file) {

        // in config.ini
        #REGEX CONTRY STORE

        $config=new ConfigManager();

        $subject = $file;
        $pattern = $config->getCountryStoreRexEx();
        preg_match($pattern, $subject, $matches);
        $country_store=$matches[1];

        $stores = Mage::getModel('core/store')->getCollection()->getData();

        $store_id=1;
        foreach ($stores as $store) {
            if ($store['country'] === $country_store) {
                $store_id=$store['store_id'];
                break;
            }
        }
        return $store_id;
    }

    public function getCountryFromStoreId($storeId) {
        $countries = Mage::getModel('core/store')
            ->getCollection()
            ->addFieldToSelect('country')
            ->addFieldToFilter('store_id',$storeId)
            ->load()
            ->getData();

        return $countries[0]['country'];
    }

    public function changeOrderStatus($increment_id)
    {

        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        //$state = 'holded';
        //$status = 'On Hold';
        //$comment = 'Cliente di spedizione da verificare';
        //$isCustomerNotified = false;
        //$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);

        //$order->hold()->addStatusHistoryComment("Cliente di spedizione da verificare");
        //$order->hold()->save();
        //$order->setState($state, $status, $comment, $isCustomerNotified);
        //$order->save();
        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);
        $order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
        //$order->hold()->addStatusHistoryComment("Cliente di spedizione da verificare");
        $order->save();
    }

    public function setCustomStatus($increment_id) {


        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
        //$order->save();
        $order->setData('state', "pending");
        $order->setStatus("pending");
        $history = $order->addStatusHistoryComment('Order marked with custom state/status automatically.', false);
        $history->setIsCustomerNotified(false);
        $order->save();

    }

}


//$t = new MagentoHelper();
//$t->setCustomStatus('100000058');