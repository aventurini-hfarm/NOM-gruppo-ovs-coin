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

Mage::app();


class MagentoOrderHelper {


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

    public function __construct(){

        $this->log = new KLogger('/var/log/nom/magento_order_helper.log',KLogger::DEBUG);
    }

    public function setShippingMethod($methodName)
    {
        $this->_shippingMethod = $methodName;
    }

    public function setPaymentMethod($methodName)
    {
        $this->_paymentMethod = $methodName;
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

    public function setCustomerDWCode($dw_customer, $file)
    {

        //echo "\nCreating CustomerDWCode\n";
        $cust_no=$dw_customer->customer_no;                                                  //RINO 10/10/2016
        $magentoHelper = new MagentoHelper();
        $store_id = $magentoHelper->getStoreIdFromFile($file);

        //echo "\nConnessione";
        $con = OMDBManager::getMagentoConnection();
        if (strlen($cust_no)>8)  {
            $tmp_cust_no = strtoupper($cust_no);
            $sql = "SELECT entity_id FROM customer_entity_varchar WHERE attribute_id=154 AND UPPER(value)='$tmp_cust_no'";
        }
        else
            $sql = "SELECT entity_id FROM customer_entity_varchar WHERE attribute_id=154 AND value='$cust_no'";
        //echo "\nSQL:".$sql;
        $res = mysql_query($sql);
        $id_customer = null;
        while ($row = mysql_fetch_object($res)) {
            $id_customer = $row->entity_id;
        }

        OMDBManager::closeConnection($con);

        //echo "\nID_CUSTOMER: |".$id_customer."|";
        if ($id_customer)
            $this->_customer = Mage::getModel('customer/customer')->load($id_customer);
        else
            $this->_customer = null;


        //print_r($this->_customer);


        if (!$this->_customer) {

            //occorre verificare se l'email esiste perchè potrebbe essere il seguente caso:
            //utente che ha già fatto ordini guest, NON registrato,  che decide di REGISTRASI (che scende con id=687) e di fare un ordine
            //utente deve essere aggiornato con userid=687 ovvero quello definitivo

            $con = OMDBManager::getMagentoConnection();
            $customer_email = $dw_customer->customer_email;
            $tmp_email = strtoupper($customer_email);
            $sql = "SELECT entity_id FROM customer_entity WHERE  UPPER(email)='$tmp_email' AND store_id=$store_id";

            //echo "\nSQL2:".$sql;
            $res = mysql_query($sql);
            $entity_id = null;
            while ($row = mysql_fetch_object($res)) {
                $entity_id = $row->entity_id;
                $this->log->LogDebug("Utente già presente in magento: ".$customer_email);
            }
            OMDBManager::closeConnection($con);

            if ($entity_id) {
                //aggiorno dati per per quell'utente
                $this->_customer = Mage::getModel('customer/customer')->load($entity_id);
                $customerObj = new CustomerObject();

                $customerObj->customer_no = $dw_customer->customer_no;
                $customerObj->email = $dw_customer->customer_email;
                $customerObj->first_name = $dw_customer->billing_first_name;
                $customerObj->last_name = $dw_customer->billing_last_name;
                $customerObj->store_id = $store_id;

                $customerHelper = new MagentoCustomerHelper();
                $customerHelper->updateMagentoUser($customerObj, $this->_customer);
                $this->log->LogDebug("\nAggiornato Utente Customer da Guest a Non Guest: ".$customerObj->customer_no." , email: ". $customerObj->email.", magento_id: ".$entity_id);
                return;

            }



            $this->log->LogDebug("\nCreazione Utente");


            $customerObj = new CustomerObject();

            $customerObj->customer_no = $dw_customer->customer_no;
            $customerObj->email = $dw_customer->customer_email;
            $customerObj->first_name = $dw_customer->billing_first_name;
            $customerObj->last_name = $dw_customer->billing_last_name;
            $customerObj->store_id = $store_id;

           // print_r($customerObj);
            $customerHelper = new MagentoCustomerHelper();
            $customerHelper->import($customerObj);

            $con = OMDBManager::getMagentoConnection();
            $sql = "SELECT entity_id FROM customer_entity_varchar WHERE attribute_id=154 AND value='$cust_no'";
            //echo "\nSQL:".$sql;
            $res = mysql_query($sql);
            $id_customer = null;
            while ($row = mysql_fetch_object($res)) {
                $id_customer = $row->entity_id;
            }

            OMDBManager::closeConnection($con);


            $this->_customer = Mage::getModel('customer/customer')->load($id_customer);
            $this->log->LogDebug("\nResult Customer: ".$this->_customer);

        }

    }

    /**
     * Non utilizzato come metodo perchè per gli utenti guest non devo creare utente in magento
     * @param $dw_customer
     * @param $file
     */
    public function setGuestCustomerDWCode($dw_customer, $file)
    {

        /**
         * procedura utente guest ma potrebbe però essere già registrato in magento
         * Caso 1:
         * utente registrato (id=10) che decide di fare un ordine guest (che scende con id=687)
         * scontrino scende con id=999 (guest)
         *
         * Caso 2:
         * utente NON registrato che decide di fare un ordine guest (che scende con id=687)
         * scontrino scende con id=999 (guest)
         */

        $this->log->LogDebug("Procedura Creazione Utente Guest");
        $cust_no=$dw_customer->customer_no;
        $customer_email = $dw_customer->customer_email;

        $magentoHelper = new MagentoHelper();
        $store_id = $magentoHelper->getStoreIdFromFile($file);

        $this->log->LogDebug("Utente Guest: ".$cust_no.", email: ".$customer_email.", store_id: ".$store_id);
        $con = OMDBManager::getMagentoConnection();
        $tmp_email = strtoupper($customer_email);
        $sql = "SELECT entity_id FROM customer_entity WHERE  UPPER(email)='$tmp_email' AND store_id=$store_id";

        //echo "\nSQL:".$sql;
        $res = mysql_query($sql);
        $entity_id = null;
        while ($row = mysql_fetch_object($res)) {
            $entity_id = $row->entity_id;
            $this->log->LogDebug("Utente Guest già presente in magento: ".$customer_email);
        }

        OMDBManager::closeConnection($con);

        //echo "\nID_CUSTOMER: |".$id_customer."|";
        if ($entity_id)
            $this->_customer = Mage::getModel('customer/customer')->load($entity_id);
        else
            $this->_customer = null;


        //print_r($this->_customer);


        if (!$this->_customer) {

            $this->log->LogDebug("\nCreazione Utente Guest: ".$cust_no.", email: ".$customer_email.", store_id: ".$store_id);
            $helper = new MagentoCustomerHelper();
            $customerObj = new CustomerObject();

            $customerObj->customer_no = $dw_customer->customer_no;
            $customerObj->email = $dw_customer->customer_email;
            $customerObj->first_name = $dw_customer->billing_first_name;
            $customerObj->last_name = $dw_customer->billing_last_name;

            $customerObj->store_id = $store_id;

            $helper->import($customerObj);

            $con = OMDBManager::getMagentoConnection();
            $sql = "SELECT entity_id FROM customer_entity_varchar WHERE attribute_id=154 AND value='$cust_no'";
            //echo "\nSQL:".$sql;
            $res = mysql_query($sql);
            $id_customer = null;
            while ($row = mysql_fetch_object($res)) {
                $id_customer = $row->entity_id;
            }

            OMDBManager::closeConnection($con);


            $this->_customer = Mage::getModel('customer/customer')->load($id_customer);
            $this->log->LogDebug("\nResult Guest CustomerId (magento): ".$id_customer);

        }

    }

    public function setCustomerDWCodeRino($dw_customer, $file)
    {

        $cust_no=ltrim($dw_customer->customer_no,'0');                                                   //RINO 10/10/2016
        $data = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('customer_id')
            //->addAttributeToFilter('customer_no',$dw_customer->customer_no)->load()->getData();
            ->addAttributeToFilter('customer_no',  array('like' => '%'.$cust_no ) )->load()->getData(); // RINO 10/10/2016

        $id_customer = $data[0]['entity_id'];

        //echo "\nID_CUSTOMER: |".$id_customer."|";
        if ($id_customer)
            $this->_customer = Mage::getModel('customer/customer')->load($id_customer);
        else
            $this->_customer = null;

        //print_r($this->_customer);


        if (!$this->_customer) {
            $this->log->LogDebug("\nCreazione Utente");
            $helper = new MagentoCustomerHelper();
            $customerObj = new CustomerObject();

            $customerObj->customer_no = $dw_customer->customer_no;
            $customerObj->email = $dw_customer->customer_email;
            $customerObj->first_name = $dw_customer->billing_first_name;
            $customerObj->last_name = $dw_customer->billing_last_name;

            $magentoHelper = new MagentoHelper();
            $customerObj->store_id = $magentoHelper->getStoreIdFromFile($file);   //RINO 05/07/2016

            $helper->import($customerObj);


            $data = Mage::getModel('customer/customer')
                ->getCollection()
                ->addAttributeToSelect('customer_id')
                ->addAttributeToFilter('customer_no',$dw_customer->customer_no)->load()->getData();

            $id_customer = $data[0]['entity_id'];

            $this->_customer = Mage::getModel('customer/customer')->load($id_customer);
            $this->log->LogDebug("\nResult Customer: ".$this->_customer);

        }

    }

    private function getSubInventoryList() {

        $lista =array();
        foreach ($this->dw_order->lista_prodotti as $line) {
            $lista[$line['subinventory']] = $line['subinventory'];
        }

        return $lista;

    }

    public function createOrderRino(OrderObject $dw_order, $file)
    {
        $this->dw_order = $dw_order;

        $transaction = Mage::getModel('core/resource_transaction');
        $this->_storeId = MagentoHelper::getStoreIdFromFile($file);   //RINO 05/07/2016

        $reservedOrderId = Mage::getSingleton('eav/config')
            ->getEntityType('order')
            ->fetchNewIncrementId($this->_storeId);

        $currencyCode  = Mage::app()->getBaseCurrencyCode();
        $this->_order = Mage::getModel('sales/order')
            ->setIncrementId($reservedOrderId)
            ->setStoreId($this->_storeId)
            ->setQuoteId(0)
            ->setGlobalCurrencyCode($currencyCode)
            ->setBaseCurrencyCode($currencyCode)
            ->setStoreCurrencyCode($currencyCode)
            ->setOrderCurrencyCode($currencyCode)
            ->setCustomerLocale($this->dw_order->customer_locale);


        $this->_order->setCustomerEmail($this->_customer->getEmail())
            ->setCustomerFirstname($this->_customer->getFirstname())
            ->setCustomerLastname($this->_customer->getLastname())
            ->setCustomerGroupId($this->_customer->getGroupId())
            ->setCustomerIsGuest(0)
            ->setCustomer($this->_customer);


        $billingAddress = Mage::getModel('sales/order_address')
            ->setStoreId($this->_storeId)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
            ->setCustomerId($this->_customer->getId())
            ->setFirstname($dw_order->customer->billing_first_name)
            ->setLastname($dw_order->customer->billing_last_name)
            ->setStreet($dw_order->customer->billing_address1)
            ->setCity($dw_order->customer->billing_city)
            ->setCountry_id($dw_order->customer->billing_country_code)
            ->setRegion($dw_order->customer->billing_state_code)
            ->setPostcode($dw_order->customer->billing_postal_code)
            ->setTelephone($dw_order->customer->billing_phone);

        if ($dw_order->need_invoice=='true') {
            $billingAddress->setVatId($dw_order->codiceFiscale);
        }

        $this->_order->setBillingAddress($billingAddress);


        $shippingAddress = Mage::getModel('sales/order_address')
            ->setStoreId($this->_storeId)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
            ->setCustomerId($this->_customer->getId())
            ->setFirstname($dw_order->shipment->shipping_first_name)
            ->setLastname($dw_order->shipment->shipping_last_name)
            ->setStreet($dw_order->shipment->shipping_address1)
            ->setCity($dw_order->shipment->shipping_city)
            ->setCountry_id($dw_order->shipment->shipping_country_code)
            ->setRegion($dw_order->shipment->shipping_state_code)
            ->setPostcode($dw_order->shipment->shipping_postal_code)
            //->setShippingMethod($dw_order->shipment->shipping_method)
            //->setCarrier('SDA')   //RINO 21/07/2016 per ovs si usa la tabella estero_light
            ->setNote($dw_order->shipment->shipping_address2)
            ->setTelephone($dw_order->shipment->shipping_phone);

        $shippingAddress->setData('assemblydeliveryfloor',$dw_order->shipment->assemblyDeliveryFloor);
        $shippingAddress->setData('assemblydeliverylift',$dw_order->shipment->assemblyDeliveryLift);
        $shippingAddress->setData('assemblydeliverypropertytype',$dw_order->shipment->assemblyDeliveryPropertyType);
        $shippingAddress->setData('assemblydeliverynote',$dw_order->shipment->assemblyDeliveryNote);

        //echo "\nDUMP SHIPPING ADDRESS";
        //print_r($shippingAddress);
        //$shippingPrice = 20;
        // Mage::register('shipping_cost', $shippingPrice);



        //$this->_order->setShippingDescription('Flat Rate - Fixed');
        $this->_shippingMethod ="flatrate_flatrate";
        $this->_shippingDescription ="Flat Rate - Fixed";



        switch ($dw_order->shipment->shipping_method) {
            case "ClickAndCollect":
                $this->_shippingMethod = "smashingmagazine_mycarrier_standard";
                $this->_shippingDescription = "ClickAndCollect";
                $this->_order->setData('store_code_pick',$dw_order->store_code_pick);
                break;
            case "Forniture":
                $this->_shippingMethod = "excellence_Forniture";
                $this->_shippingDescription = "Forniture";
                break;
            case "Express":
                $this->_shippingMethod = "Express";
                $this->_shippingDescription = "Express";
                break;
            default:
                $this->_shippingMethod = "flatrate_flatrate";
                $this->_shippingDescription = "Flat Rate - Fixed";
                break;
        }


        $shippingAddress->setShippingMethod($this->_shippingMethod)->setCollectShippingRates(true);
        $this->_order->setShippingAddress($shippingAddress);
        $this->_order->setShippingDescription($this->_shippingDescription);
        $this->_order->setShippingMethod($this->_shippingMethod);

        $this->_order->setDwOrderNumber($dw_order->order_no);
        $this->_order->setDwOrderDatetime($dw_order->order_date);

        $this->_order->setCustomerName($dw_order->customer->customer_name); //26/01/2016


        $this->_order->setData('needInvoice',$dw_order->need_invoice);

        /*
         *  gestiti con apposita tabella nel db
        $this->_order->setCodiceFiscale($dw_order->codiceFiscale);


        $this->_order->setData('loyaltyCard',$dw_order->loyaltyCard);
        $this->_order->setdata('rewardPoints',$dw_order->rewardPoints);
        */

        $this->_order->setDwCustomerId($dw_order->customer->customer_no);
        $newDate_ordine = date("Y-m-d H:i:s", strtotime($dw_order->order_date));
        $this->_order->setCreatedAt($newDate_ordine);


//        print_r($this->_order->getShippingAdress());
        //$shippingAddress->setCollectShippingRates(true)->collectShippingRates()->
        //    setShippingMethod('flatrate_flatrate')->setPaymentMethod('checkmo');


        //$this->_order->setShippingAddress($shippingAddress);
        if ($dw_order->payment->type=="CYBERSOURCE") {
            $this->_paymentMethod = "ccsave";
        }

        if ($dw_order->payment->type=="PAYPAL") {
            $this->_paymentMethod = "paypal_standard";
        }

        if ($dw_order->payment->type=="CONTRASSEGNO") {
            $this->_paymentMethod = "cashondelivery";
        }

        if ($dw_order->payment->type=="CHIOSCO") {
            $allAvailablePaymentMethods = Mage::getModel('payment/config')->getAllMethods();
            $this->_paymentMethod = "free";
        }

        $orderPayment = Mage::getModel('sales/order_payment')
            ->setStoreId($this->_storeId)
            ->setCustomerPaymentId(0)
            ->setMethod($this->_paymentMethod)
            ->setPoNumber(' – ');

        if ($dw_order->payment->type=="CYBERSOURCE") {

            $orderPayment->setCcExpYear(intval($dw_order->payment->expiration_year));
            $orderPayment->setCcExpMonth(intval($dw_order->payment->expiration_month));
            $orderPayment->setCcType($dw_order->payment->card_type);
            $orderPayment->setCcNumber($dw_order->payment->card_number);
            $orderPayment->setCcNumberEnc($dw_order->payment->card_number);
            $orderPayment->setCcLast4(substr($dw_order->payment->card_number,-4,4));

            $orderPayment->setCcOwner($dw_order->payment->card_holder);
            $orderPayment->setAmountAuthorized($dw_order->payment->amount);


        }
        if ($dw_order->payment->type=="PAYPAL") {

            $orderPayment->setAmountAuthorized($dw_order->payment->amount);
        }

        $this->_order->setPayment($orderPayment);


        //echo "\nPRINT:";
        //print_r($this->_order->getShippingAddress()->getData());

        //Custruisce la lista products

        $lista_item = $dw_order->lista_prodotti;
        $products = array();
        foreach ($lista_item as $item) {
            $sku = $item->product_id;
            $qta = (int)$item->quantity;
            $id = Mage::getModel('catalog/product')->getIdBySku($sku);
            $products[] = array('product'=>$id, 'qty'=>$qta);
        }
        $this->log->LogDebug("DUMP PRODUCTS");
        //print_r($products);
        $this->_addProducts($products);

        //$this->_subTotal = $dw_order->totals->order_total['gross-price'];                      // RINO 30/07/2016

        // RINO 30/07/2016
        /*$this->_order->setSubtotal($this->_subTotal)
            ->setBaseSubtotal($this->_subTotal)
            ->setGrandTotal($this->_subTotal)
            ->setBaseGrandTotal($this->_subTotal);*/


        $this->_order
            ->setSubtotal($this->_subTotal)                                                      // RINO 02/08/2016
            ->setBaseSubtotal($this->_subTotal)                                                  // RINO 02/08/2016
            ->setGrandTotal($dw_order->totals->order_total['gross-price'])                       // RINO 30/07/2016
            ->setBaseGrandTotal($dw_order->totals->order_total['gross-price']);                  // RINO 30/07/2016

        $iva = $dw_order->totals->order_total['tax'];
        $this->log->LogDebug("IVA: ".$iva);
        //$imponibile = $dw_order->totals->order_total['net-price'];                            // RINO 30/07/2016
        //$this->log->LogInfo("Imponibile: ".$imponibile);                                      // RINO 30/07/2016
        //$this->_order->setTaxAmount($iva); //indica l'iva                                     // RINO 30/07/2016
        //$this->_order->setBaseTaxAmount($imponibile); //indica l'imponibile                   // RINO 30/07/2016
        $this->_order
            ->setTaxAmount($iva)                                                                     // RINO 30/07/2016
            ->setBaseTaxAmount($iva)                                                                 // RINO 30/07/2016
            ->setSubtotalInclTax($dw_order->totals->merchandize_total['gross-price'])               // RINO 31/07/2016
            ->setBaseSubtotalInclTax($dw_order->totals->merchandize_total['gross-price']);          // RINO 31/07/2016

        /* RINO 30/07/2016
        //In realtà lo shipping facendo così non viene sommato ma solo presente in ordine
        $shippingPrice = $dw_order->totals->adjusted_shipping_total['gross-price'];
        $this->_order->setBaseShippingAmount($shippingPrice);
        $this->_order->setShippingAmount($shippingPrice);
        */

        //RINO 30/07/2016
        $shippingNetPrice = $dw_order->totals->adjusted_shipping_total['net-price'];    //RINO 30/07/2016
        $shippingTax = $dw_order->totals->adjusted_shipping_total['tax'];               //RINO 30/07/2016
        $shippingInclTax = $dw_order->totals->adjusted_shipping_total['gross-price'];   //RINO 30/07/2016

        $this->_order
            ->setBaseShippingAmount($shippingNetPrice)                          //RINO 30/07/2016
            ->setShippingAmount($shippingNetPrice)                              //RINO 30/07/2016
            ->setBaseShippingTaxAmount($shippingTax)                            //RINO 30/07/2016
            ->setShippingTaxAmount($shippingTax)                                //RINO 30/07/2016
            ->setShippingInclTax($shippingInclTax)                              //RINO 30/07/2016
            ->setBaseShippingInclTax($shippingInclTax);                         //RINO 30/07/2016





        $this->addMerchandizePromo($dw_order);
        $this->addShippingPromo($dw_order);




        $transaction->addObject($this->_order);
        $transaction->addCommitCallback(array($this->_order, 'place'));
        $transaction->addCommitCallback(array($this->_order, 'save'));
        $transaction->save();


    }

    public function createOrder(OrderObject $dw_order, $file)
    {
        $this->dw_order = $dw_order;

        $transaction = Mage::getModel('core/resource_transaction');
        $this->_storeId = MagentoHelper::getStoreIdFromFile($file);   //RINO 05/07/2016

        $reservedOrderId = Mage::getSingleton('eav/config')
            ->getEntityType('order')
            ->fetchNewIncrementId($this->_storeId);

        $currencyCode  = Mage::app()->getBaseCurrencyCode();
        $this->_order = Mage::getModel('sales/order')
            ->setIncrementId($reservedOrderId)
            ->setStoreId($this->_storeId)
            ->setQuoteId(0)
            ->setGlobalCurrencyCode($currencyCode)
            ->setBaseCurrencyCode($currencyCode)
            ->setStoreCurrencyCode($currencyCode)
            ->setOrderCurrencyCode($currencyCode)
            ->setCustomerLocale($this->dw_order->customer_locale);

        if ($dw_order->isAuthenticated=='false') {
            //$this->_order->setData('dw_guest_userid', $dw_order->_customer->customer_no);
            $this->_order->setCustomerEmail($dw_order->customer->customer_email)
                ->setCustomerFirstname($dw_order->customer->customer_name)
                ->setCustomerIsGuest(1);

        }  else {
        if (!$this->_customer) {
            echo "\nAttenzione ordine senza customer: ".$dw_order->order_no;
            die();
        }
        $this->_order->setCustomerEmail($this->_customer->getEmail())
            ->setCustomerFirstname($this->_customer->getFirstname())
            ->setCustomerLastname($this->_customer->getLastname())
            ->setCustomerGroupId($this->_customer->getGroupId())
            ->setCustomerIsGuest(0)
            ->setCustomer($this->_customer);
        }


        if ($dw_order->isAuthenticated=='false') {

            $billingAddress = Mage::getModel('sales/order_address')
                ->setStoreId($this->_storeId)
                ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
                ->setFirstname($dw_order->customer->billing_first_name)
                ->setLastname($dw_order->customer->billing_last_name)
                ->setStreet($dw_order->customer->billing_address1)
                ->setCity($dw_order->customer->billing_city)
                ->setCountry_id($dw_order->customer->billing_country_code)
                ->setRegion($dw_order->customer->billing_state_code)
                ->setPostcode($dw_order->customer->billing_postal_code)
                ->setTelephone($dw_order->customer->billing_phone);
        } else {

        $billingAddress = Mage::getModel('sales/order_address')
            ->setStoreId($this->_storeId)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
            //->setCustomerId($this->_customer->getId())
            ->setFirstname($dw_order->customer->billing_first_name)
            ->setLastname($dw_order->customer->billing_last_name)
            ->setStreet($dw_order->customer->billing_address1)
            ->setCity($dw_order->customer->billing_city)
            ->setCountry_id($dw_order->customer->billing_country_code)
            ->setRegion($dw_order->customer->billing_state_code)
            ->setPostcode($dw_order->customer->billing_postal_code)
            ->setTelephone($dw_order->customer->billing_phone);
        }

        if ($dw_order->need_invoice=='true') {
            $billingAddress->setVatId($dw_order->codiceFiscale);
        }

        $this->_order->setBillingAddress($billingAddress);

        if ($dw_order->isAuthenticated=='false') {
        $shippingAddress = Mage::getModel('sales/order_address')
            ->setStoreId($this->_storeId)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
            ->setFirstname($dw_order->shipment->shipping_first_name)
            ->setLastname($dw_order->shipment->shipping_last_name)
            ->setStreet($dw_order->shipment->shipping_address1)
            ->setCity($dw_order->shipment->shipping_city)
            ->setCountry_id($dw_order->shipment->shipping_country_code)
            ->setRegion($dw_order->shipment->shipping_state_code)
            ->setPostcode($dw_order->shipment->shipping_postal_code)
            //->setShippingMethod($dw_order->shipment->shipping_method)
            //->setCarrier('SDA')   //RINO 21/07/2016 per ovs si usa la tabella estero_light
            ->setNote($dw_order->shipment->shipping_address2)
            ->setTelephone($dw_order->shipment->shipping_phone);
        } else {
            $shippingAddress = Mage::getModel('sales/order_address')
                ->setStoreId($this->_storeId)
                ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
                //->setCustomerId($this->_customer->getId())
                ->setFirstname($dw_order->shipment->shipping_first_name)
                ->setLastname($dw_order->shipment->shipping_last_name)
                ->setStreet($dw_order->shipment->shipping_address1)
                ->setCity($dw_order->shipment->shipping_city)
                ->setCountry_id($dw_order->shipment->shipping_country_code)
                ->setRegion($dw_order->shipment->shipping_state_code)
                ->setPostcode($dw_order->shipment->shipping_postal_code)
                //->setShippingMethod($dw_order->shipment->shipping_method)
                //->setCarrier('SDA')   //RINO 21/07/2016 per ovs si usa la tabella estero_light
                ->setNote($dw_order->shipment->shipping_address2)
                ->setTelephone($dw_order->shipment->shipping_phone);

        }

        $shippingAddress->setData('assemblydeliveryfloor',$dw_order->shipment->assemblyDeliveryFloor);
        $shippingAddress->setData('assemblydeliverylift',$dw_order->shipment->assemblyDeliveryLift);
        $shippingAddress->setData('assemblydeliverypropertytype',$dw_order->shipment->assemblyDeliveryPropertyType);
        $shippingAddress->setData('assemblydeliverynote',$dw_order->shipment->assemblyDeliveryNote);

        //echo "\nDUMP SHIPPING ADDRESS";
        //print_r($shippingAddress);
        //$shippingPrice = 20;
        // Mage::register('shipping_cost', $shippingPrice);



        //$this->_order->setShippingDescription('Flat Rate - Fixed');
        $this->_shippingMethod ="flatrate_flatrate";
        $this->_shippingDescription ="Flat Rate - Fixed";



        switch ($dw_order->shipment->shipping_method) {
            case "ClickAndCollect":
                $this->_shippingMethod = "smashingmagazine_mycarrier_standard";
                $this->_shippingDescription = "ClickAndCollect";
                $this->_order->setData('store_code_pick',$dw_order->store_code_pick);
                break;
            case "Forniture":
                $this->_shippingMethod = "excellence_Forniture";
                $this->_shippingDescription = "Forniture";
                break;
            case "Express":
                $this->_shippingMethod = "Express";
                $this->_shippingDescription = "Express";
                break;
            default:
                $this->_shippingMethod = "flatrate_flatrate";
                $this->_shippingDescription = "Flat Rate - Fixed";
                break;
        }


        $shippingAddress->setShippingMethod($this->_shippingMethod)->setCollectShippingRates(true);
        $this->_order->setShippingAddress($shippingAddress);
        $this->_order->setShippingDescription($this->_shippingDescription);
        $this->_order->setShippingMethod($this->_shippingMethod);

        $this->_order->setDwOrderNumber($dw_order->order_no);
        $this->_order->setDwOrderDatetime($dw_order->order_date);

        $this->_order->setCustomerName($dw_order->customer->customer_name); //26/01/2016


        $this->_order->setData('needInvoice',$dw_order->need_invoice);

        $this->_order->setData('shipping_method_dw', $dw_order->shipment->shipping_method);
        $this->_order->setData('vs_flag',1);


        $this->_order->setData('dw_is_authenticated', $dw_order->isAuthenticated);

        /*
         *  gestiti con apposita tabella nel db
        $this->_order->setCodiceFiscale($dw_order->codiceFiscale);


        $this->_order->setData('loyaltyCard',$dw_order->loyaltyCard);
        $this->_order->setdata('rewardPoints',$dw_order->rewardPoints);
        */

        $this->_order->setDwCustomerId($dw_order->customer->customer_no);
        $newDate_ordine = date("Y-m-d H:i:s", strtotime($dw_order->order_date));
        $this->_order->setCreatedAt($newDate_ordine);


//        print_r($this->_order->getShippingAdress());
        //$shippingAddress->setCollectShippingRates(true)->collectShippingRates()->
        //    setShippingMethod('flatrate_flatrate')->setPaymentMethod('checkmo');


        //$this->_order->setShippingAddress($shippingAddress);
        if ($dw_order->payment->type=="CYBERSOURCE") {
            $this->_paymentMethod = "ccsave";
        }

        if ($dw_order->payment->type=="PAYPAL") {
            $this->_paymentMethod = "paypal_standard";
        }

        if ($dw_order->payment->type=="CONTRASSEGNO") {
            $this->_paymentMethod = "cashondelivery";
        }

        if ($dw_order->payment->type=="CHIOSCO") {
            $allAvailablePaymentMethods = Mage::getModel('payment/config')->getAllMethods();
            $this->_paymentMethod = "free";
        }

        $orderPayment = Mage::getModel('sales/order_payment')
            ->setStoreId($this->_storeId)
            ->setCustomerPaymentId(0)
            ->setMethod($this->_paymentMethod)
            ->setPoNumber(' – ');

        if ($dw_order->payment->type=="CYBERSOURCE") {

            $orderPayment->setCcExpYear(intval($dw_order->payment->expiration_year));
            $orderPayment->setCcExpMonth(intval($dw_order->payment->expiration_month));
            $orderPayment->setCcType($dw_order->payment->card_type);
            $orderPayment->setCcNumber($dw_order->payment->card_number);
            $orderPayment->setCcNumberEnc($dw_order->payment->card_number);
            $orderPayment->setCcLast4(substr($dw_order->payment->card_number,-4,4));

            $orderPayment->setCcOwner($dw_order->payment->card_holder);
            $orderPayment->setAmountAuthorized($dw_order->payment->amount);


        }
        if ($dw_order->payment->type=="PAYPAL") {

            $orderPayment->setAmountAuthorized($dw_order->payment->amount);
        }

        $this->_order->setPayment($orderPayment);


        //echo "\nPRINT:";
        //print_r($this->_order->getShippingAddress()->getData());

        //Custruisce la lista products

        $lista_item = $dw_order->lista_prodotti;
        $products = array();
        foreach ($lista_item as $item) {
            $sku = $item->product_id;
            $qta = (int)$item->quantity;
            $id = Mage::getModel('catalog/product')->getIdBySku($sku);
            $products[] = array('product'=>$id, 'qty'=>$qta);
        }
        $this->log->LogDebug("DUMP PRODUCTS");
        //print_r($products);
        $this->_addProducts($products);

        $this->_subTotal = $dw_order->totals->order_total['gross-price'];                      // RINO 30/07/2016

        $this->_order->setSubtotal($this->_subTotal)
            ->setBaseSubtotal($this->_subTotal)
            ->setGrandTotal($this->_subTotal)
            ->setBaseGrandTotal($this->_subTotal);



        $iva = $dw_order->totals->order_total['tax'];
        $this->log->LogDebug("IVA: ".$iva);
        $imponibile = $dw_order->totals->order_total['net-price'];
        $this->log->LogInfo("Imponibile: ".$imponibile);
        $this->_order->setTaxAmount($iva); //indica l'iva
        $this->_order->setBaseTaxAmount($imponibile); //indica l'imponibile





        //In realtà lo shipping facendo così non viene sommato ma solo presente in ordine
        $shippingPrice = $dw_order->totals->adjusted_shipping_total['gross-price'];
        $this->_order->setBaseShippingAmount($shippingPrice);
        $this->_order->setShippingAmount($shippingPrice);

        $this->addMerchandizePromo($dw_order);
        $this->addShippingPromo($dw_order);



        $transaction->addObject($this->_order);
        $transaction->addCommitCallback(array($this->_order, 'place'));
        $transaction->addCommitCallback(array($this->_order, 'save'));
        $transaction->save();


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

        $ordini_da_aggiornare = array("00291263");



        //$ordini_da_aggiornare = array('00289503');
        //$ordini_da_aggiornare = array();



        if ( !in_array($order->dw_order_number, $ordini_da_aggiornare)) {
            //echo "\nORdine scartato perchè non di interesse: ".$dw_order->order_no."\n";
            return;
        }

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
            base_row_total=$rowTotal, discount_amount=$rowDiscount
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

    }


    private function addShippingPromo(OrderObject $order) {

        $shipping = $order->shipping;
        if ($shipping->promotion_id) {
            //inserisce i dati addizionali sul DB
            $value = $shipping->discount_gross_price;
            $this->_order->setBaseShippingDiscountAmount($value);
            $this->_order->setShippingDiscountAmount($value);

        }
    }

    private function addMerchandizePromoRino(OrderObject $order) {

        $merchandize_total = $order->totals->merchandize_total_price_adjustments_price_adjustment;
        $value = 0;
        $description = array();;
        foreach ($merchandize_total as $item) {
            if ($item['promotion-id']) {
                //inserisce i dati addizionali sul DB
                //$value += ($item['discount-gross-price'] * -1);               //RINO  01/08/2016
                $value += $item['discount-net-price'];                          //RINO  01/08/2016

                $description[] = $item['promotion-id'];
            }
        }

        // RINO TODO aggiungere il netto ti tutto gli sconti in linea
        $value+=$this->_row_discount_total;


        $this->_order->setBaseDiscountAmount($value);
        $this->_order->setDiscountAmount($value);
        $this->_order->setDiscountDescription(implode("|", $description));

    }

    private function addMerchandizePromo(OrderObject $order) {

        $merchandize_total = $order->totals->merchandize_total;
        $value = 0;
        $description = array();

        foreach ($merchandize_total as $item) {
           // print_r($item);
            if ($item['promotion-id']) {
                //inserisce i dati addizionali sul DB
                $value += ($item['discount-gross-price'] * -1);
                $description[] = $item['promotion-id'];
            }
        }
        echo "\nMerchandize Promo: \n";
        //print_r($description);
        $this->_order->setBaseDiscountAmount($value);
        $this->_order->setDiscountAmount($value);
        $this->_order->setDiscountDescription(implode("|", $description));

    }

    protected function _addProducts($products)
    {
        $this->_subTotal = 0;
        $this->_row_discount_total = 0;

        foreach ($products as $productRequest) {

            $this->_addProduct($productRequest);
        }
    }

    protected function _addProduct($requestData)
    {
        $request = new Varien_Object();
        $request->setData($requestData);

        $product = Mage::getModel('catalog/product')->load($request['product']);

        $cartCandidates = $product->getTypeInstance(true)
            ->prepareForCartAdvanced($request, $product);

        if (is_string($cartCandidates)) {
            throw new Exception($cartCandidates);
        }

        if (!is_array($cartCandidates)) {
            $cartCandidates = array($cartCandidates);
        }

        $parentItem = null;
        $errors = array();
        $items = array();


        foreach ($cartCandidates as $candidate) {

            $item = $this->_productToOrderItem($candidate, $candidate->getCartQty());

            $items[] = $item;

            /**
             * As parent item we should always use the item of first added product
             */
            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId()) {
                $item->setParentItem($parentItem);
            }
            /**
             * We specify qty after we know about parent (for stock)
             */
            $item->setQty($item->getQty() + $candidate->getCartQty());

            // collect errors instead of throwing first one
            if ($item->getHasError()) {
                $message = $item->getMessage();
                if (!in_array($message, $errors)) { // filter duplicate messages
                    $errors[] = $message;
                }
            }
        }
        if (!empty($errors)) {
            Mage::throwException(implode("\n", $errors));
        }

        foreach ($items as $item){
            $this->_order->addItem($item);
        }

        return $items;
    }

    function _productToOrderItem(Mage_Catalog_Model_Product $product, $qty = 1)
    {
        $rowTotal = $product->getFinalPrice() * $qty;

        $options = $product->getCustomOptions();

        $optionsByCode = array();

        foreach ($options as $option)
        {
            $quoteOption = Mage::getModel('sales/quote_item_option')->setData($option->getData())
                ->setProduct($option->getProduct());

            $optionsByCode[$quoteOption->getCode()] = $quoteOption;
        }

        $product->setCustomOptions($optionsByCode);

        $options = $product->getTypeInstance(true)->getOrderOptions($product);

        $customPrice = 0;
        $rowTotal = $customPrice * $qty;

        $lista_item = $this->dw_order->lista_prodotti;

        foreach ($lista_item as $item) {
            $sku = $item->product_id;
            if ($sku == $product->getSku()) {
                $customPrice = $item->base_price;
                $rowTotal = $item->gross_price;
                $rowDiscount = -1 * (float)$item->discount_gross_price; //indica lo sconto complessivo (ovvero già moltiplicato per le qta)
                $rowPromotionId = $item->promotion_id;
                $rowCampaignId = $item->campaign_id;
                $hasOptions = $item->has_options;
                $rowTax = $item->tax;
                //TODO inserire qui gli attributi custom delle righe ordine (extra punti e punt resi)
                break;
            }
        }

        /*
                $orderItem = Mage::getModel('sales/order_item')
                    ->setStoreId($this->_storeId)
                    ->setQuoteItemId(0)
                    ->setQuoteParentItemId(NULL)
                    ->setProductId($product->getId())
                    ->setProductType($product->getTypeId())
                    ->setQtyBackordered(NULL)
                    ->setTotalQtyOrdered($product['rqty'])
                    ->setQtyOrdered($product['qty'])
                    ->setName($product->getName())
                    ->setSku($product->getSku())
                    //->setPrice($product->getFinalPrice())
                    ->setPrice($customPrice)
                    //->setTaxAmount($rowTax)
                    //->setBasePrice($product->getFinalPrice())
                    ->setBasePrice($customPrice)
                    //->setOriginalPrice($product->getFinalPrice())
                    ->setOriginalPrice($customPrice)
                    ->setRowTotal($rowTotal)
                    ->setBaseRowTotal($rowTotal)
                    ->setItemDwPromoId($rowPromotionId)
                    ->setDiscountAmount($rowDiscount)
                    ->setItemHasOptions($hasOptions)

                    ->setWeeeTaxApplied(serialize(array()))
                    ->setBaseWeeeTaxDisposition(0)
                    ->setWeeeTaxDisposition(0)
                    ->setBaseWeeeTaxRowDisposition(0)
                    ->setWeeeTaxRowDisposition(0)
                    ->setBaseWeeeTaxAppliedAmount(0)
                    ->setBaseWeeeTaxAppliedRowAmount(0)
                    ->setWeeeTaxAppliedAmount(0)
                    ->setWeeeTaxAppliedRowAmount(0)

                    ->setProductOptions($options);
        */

        $orderItem = Mage::getModel('sales/order_item')
            ->setStoreId($this->_storeId)
            ->setQuoteItemId(0)
            ->setQuoteParentItemId(NULL)
            ->setProductId($product->getId())
            ->setProductType($product->getTypeId())
            ->setQtyBackordered(NULL)
            ->setTotalQtyOrdered($product['rqty'])
            ->setQtyOrdered($product['qty'])
            ->setName($product->getName())
            ->setSku($product->getSku())

            ->setPrice($customPrice)
            ->setBasePrice($customPrice)

            ->setOriginalPrice($customPrice)
            ->setRowTotal($rowTotal)
            ->setBaseRowTotal($rowTotal)
            ->setItemDwPromoId($rowPromotionId)
            ->setDiscountAmount($rowDiscount)
            //  ->setItemHasOptions($hasOptions)

            ->setWeeeTaxApplied(serialize(array()))
            ->setBaseWeeeTaxDisposition(0)
            ->setWeeeTaxDisposition(0)
            ->setBaseWeeeTaxRowDisposition(0)
            ->setWeeeTaxRowDisposition(0)
            ->setBaseWeeeTaxAppliedAmount(0)
            ->setBaseWeeeTaxAppliedRowAmount(0)
            ->setWeeeTaxAppliedAmount(0)
            ->setWeeeTaxAppliedRowAmount(0)

            ->setProductOptions($options);
        //TODO aggiungere la gestione degli attributi custom delle righe ordine
        $this->_subTotal += $rowTotal;

        return $orderItem;
    }

    function _productToOrderItemRino(Mage_Catalog_Model_Product $product, $qty = 1)
    {
        $rowTotal = $product->getFinalPrice() * $qty;

        $options = $product->getCustomOptions();

        $optionsByCode = array();

        foreach ($options as $option)
        {
            $quoteOption = Mage::getModel('sales/quote_item_option')->setData($option->getData())
                ->setProduct($option->getProduct());

            $optionsByCode[$quoteOption->getCode()] = $quoteOption;
        }

        $product->setCustomOptions($optionsByCode);

        $options = $product->getTypeInstance(true)->getOrderOptions($product);



        //  CALCOLO ripartizione sconto a carrello sulle singole righe d'ordine TODO  test applico solo il primo sconto
        $discount_net_price= -1 * $this->dw_order->totals->merchandize_total_price_adjustments_price_adjustment[0]["discount-net-price"];
        $total_net_price = $this->dw_order->totals->merchandize_total["net-price"];

        $customPrice = 0;
        $rowTotal = $customPrice * $qty;

        $lista_item = $this->dw_order->lista_prodotti;

        foreach ($lista_item as $item) {
            $sku = $item->product_id;
            if ($sku == $product->getSku()) {
                //$customPrice = $item->base_price;
                $customPrice  = $item->base_price / (1 + $item->tax_rate)  ;   //RINO 04/10/2016    // prezzo netto unitario
                //$customPrice  = $item->net_price / $item->quantity ;   //RINO 02/08/2016    // prezzo netto unitario
                $grossPrice   = $item->base_price;   //RINO 02/08/2016  // prezzo lordo unitario
                $totalInclTax = $item->gross_price;   //RINO 02/08/2016  // prezzo totale lordo incluso tasse
                //$rowTotal = $item->gross_price;
                //$rowTotal = $item->gross_price;
                //$rowTotal = $item->net_price;     //RINO 02/08/2016
                $rowDiscount = -1 * (float)$item->discount_net_price; //indica lo sconto complessivo (ovvero già moltiplicato per le qta) TODO esiste il discount net price?

                // inizio riparatizione
                $quota_price =(( $customPrice *  $item->quantity )*100 ) / $total_net_price;
                $quota_sconto_ripartito = ($quota_price * $discount_net_price) / 100;
                $rowDiscount+=$quota_sconto_ripartito;
                //$netto_meno_sconto = $item->net_price - $rowDiscount;
                $netto_meno_sconto = ( $customPrice *  $item->quantity ) - $rowDiscount; //RINO 04/10/2016
                $tax_quota_sconto_ripartito = $netto_meno_sconto * $item->tax_rate;
                // fine ripartione

                //$rowTotal=( $customPrice *  $item->quantity ); // + $item->discount_net_price; //RINO 02/08/2016
                //$rowTotal = $item->net_price;
                $rowTotal=( $customPrice *  $item->quantity );  //RINO 04/10/2016

                $rowPromotionId = $item->promotion_id;
                $rowCampaignId = $item->campaign_id;
                $hasOptions = $item->item_has_options;
                //$rowTax = $item->tax;
                $rowTax = $tax_quota_sconto_ripartito;      //RINO 02/08/2016
                $rowTaxPercent = $item->tax_rate * 100;     //RINO 02/08/2016

                $originalDiscount=$item->original_discount;

                $this->_subTotal += $rowTotal;
                $this->_row_discount_total += $item->discount_net_price;

                //TODO inserire qui gli attributi custom delle righe ordine (extra punti e punt resi)
                break;
            }
        }


        $orderItem = Mage::getModel('sales/order_item')
            ->setStoreId($this->_storeId)
            ->setQuoteItemId(0)
            ->setQuoteParentItemId(NULL)
            ->setProductId($product->getId())
            ->setProductType($product->getTypeId())
            ->setQtyBackordered(NULL)
            ->setTotalQtyOrdered($product['rqty'])
            ->setQtyOrdered($product['qty'])
            ->setName($product->getName())
            ->setSku($product->getSku())
            //->setPrice($product->getFinalPrice())
            ->setPrice($customPrice)
            ->setBasePrice($customPrice)            //RINO 02/08/2016

            ->setTaxAmount($rowTax)                 //RINO 02/08/2016
            ->setBaseTaxAmount($rowTax)             //RINO 02/08/2016

            ->setTotalInclTax($totalInclTax)        //RINO 02/08/2016
            ->setBaseTotalInclTax($totalInclTax)    //RINO 02/08/2016

            //->setBasePrice($product->getFinalPrice())
            //->setOriginalPrice($product->getFinalPrice())
            ->setOriginalPrice($customPrice)
            ->setBaseOriginalPrice($customPrice)    //RINO 02/08/2016
            ->setRowTotal($rowTotal)
            ->setBaseRowTotal($rowTotal)

            ->setTax($rowTax)                       //RINO 02/08/2016
            ->setTaxPercent($rowTaxPercent)         //RINO 02/08/2016

            ->setPriceInclTax($grossPrice)
            ->setBasePriceInclTax($grossPrice)     //RINO 02/08/2016

            ->setItemDwPromoId($rowPromotionId)

            ->setDiscountAmount($rowDiscount)
            ->setBaseDiscountAmount($rowDiscount)   //RINO 02/08/2016

            ->setOriginalDiscount($originalDiscount) //RINO 03/08/2016

            ->setItemHasOptions($hasOptions)

            ->setWeeeTaxApplied(serialize(array()))
            ->setBaseWeeeTaxDisposition(0)
            ->setWeeeTaxDisposition(0)
            ->setBaseWeeeTaxRowDisposition(0)
            ->setWeeeTaxRowDisposition(0)
            ->setBaseWeeeTaxAppliedAmount(0)
            ->setBaseWeeeTaxAppliedRowAmount(0)
            ->setWeeeTaxAppliedAmount(0)
            ->setWeeeTaxAppliedRowAmount(0)

            ->setProductOptions($options);



        return $orderItem;
    }

    public static function getOrderLineDetails($id_incrementale) {
        $lines = array();
        $prices = array();
        $final_price = array();
        $discount_percent = array();
        $order = Mage::getModel('sales/order')->load($id_incrementale, 'increment_id');
        $collection = $order->getItemsCollection();

        $country_id = strtoupper(substr($order->getCustomerLocale(),0,2));          //RINO 23/09/2016
        $con = OMDBManager::getConnection();                                        //RINO 23/09/2016
        //$collection = $order->getAllItems();
        //print_r($collection);
        foreach($collection as $prod)
        {
            if ($prod->getParentItem()) continue;
            //print_r($prod->getData());


            $line = array();
            $_product = Mage::getModel('catalog/product')->load($prod->getProductId());
            //echo "\nPRODUCT FIND: ".$_product->getSku();
            $line['sku'] = $_product->getSku();
            $line['order_quantity'] = (int)$prod->getQtyOrdered();
            //$line['description'] = $_product->getDescription();
            // {  RINO 23/09/2016
            $sql ="SELECT baseName$country_id from  estero_catalog where entity_id=$prod->productId";
            $res = mysql_query($sql);
            $row = mysql_fetch_array($res);
            if ($row)
                $line['description'] = $row[0];
            else
                $line['description'] = $_product->getDescription();
            // } RINO 23/09/2016
            $line['subinventory'] = $_product->getData('subinventory');
            $line['type'] = $_product->getTypeId();
            $line['id'] = $_product->getId();
            $line['unit_price']=$prod->getData('base_price');                                                         //RINO 04/10/2016 tolto il number format
            $line['row_total'] = $prod->getData('row_total');
            $line['gross_price']=number_format( ($prod->getData('base_price')+$prod->getData('tax_amount')) ,2);      //RINO 03/08/2016
            //$line['gross_price_2']=number_format( ($prod->getData('base_price')*1.22) ,2);                          //RINO 31/08/2016
            $line['discount_value'] = number_format($prod->getDiscountAmount(),2); //TODO da aggiungere al metodo getOrderLineDetails()
            $line['discount_value_not_fmt'] = $prod->getDiscountAmount(); //RINO 06/09/2016
            $line['original_discount'] = number_format( -1 * $prod->getData('original_discount'),2); //RINO 03/08/2016
            $line['base_price'] = number_format($prod->getData('price_incl_tax'),2);
            $line['item_dw_promo_id']= $prod->getItemDwPromoId(); //TODO da aggiungere al metodo getOrderLineDetails()
            $line['item_dw_extra_points']=''; //TODO da aggiungere al metodo getOrderLineDetails()
            $line['item_dw_return_points']=''; //TODO da aggiungere al metodo getOrderLineDetails()
            $line['item_has_options']=$prod->getData('item_has_options');
            $line['tax_amount']=$prod->getData('tax_amount');      //RINO 07/09/2016
            if ($_product->getTypeId()=='giftcard') {
                $cards = $prod->getProductOptions();
                //print_r($cards);
                $line['giftcard_code'] = $cards['giftcard_created_codes'][0];
                $line['giftcard_amount'] = $cards['info_buyRequest']['giftcard_amount'];
                $line['giftcard_recipient_email'] = $cards['info_buyRequest']['giftcard_recipient_email'];
                $line['giftcard_lifetime'] = $cards['giftcard_lifetime'];


            }

            if ($line['sku']=='') $line['sku'] = $prod->getSku(); //FIX 19/12/2013 ordine 100000322
            $lines[] = $line;
            $_product = NULL;
            $_product = null;
            unset($_product);


        }//end for
        OMDBManager::closeConnection($con);     //RINO 23/09/2016

        return $lines;
    }

    public function setStatusComplete($increment_id) {


        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
        //$order->save();
        $order->setData('state', "complete");
        $order->setStatus("complete");
        $history = $order->addStatusHistoryComment('Order marked as complete automatically.', false);
        $history->setIsCustomerNotified(false);
        $order->save();

    }


    public function doInvoice($dw_order_no){
        // $order=Mage::getModel('sales/order')->load($orderId);
        $increment_id = $this->getOrderIdByDWId($dw_order_no);
        $order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);


        $items = array();
        foreach ($order->getAllItems() as $item) {
            $items[$item->getId()] = $item->getQtyOrdered();
        }

        $invoiceId=Mage::getModel('sales/order_invoice_api')->create($order->getIncrementId(),$items,null,false,true);


        // Rino 29/06/2016
        // l'istruzione di create precedente imposta lo stato della fattura a PAID.
        // Il capture di sotto remmato non viene eseguito per un controllo CanCapture che inibisce la capture per le fatture pagate.
        // il caputure viene tuttavia già eseguito con delle classi del NOM PaymentProcessor
        // controllare se in effetti è ininfluente togliere questa capture di seguito
        //Mage::getModel('sales/order_invoice_api')->capture($invoiceId);

    }

    /**
     * @param $increment_id
     * @param $transactionID
     * @param $auth_id
     * @param $typename
     * @param $comment
     * @param int $closed
     */
    public function setTransaction($increment_id, $transactionID, $auth_id, $typename, $comment, $closed=1){
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $payment = $order->getPayment();
        $payment->setTransactionId($transactionID."/".$auth_id);
        $transaction = $payment->addTransaction($typename, null, false, $comment);
        $transaction->setParentTxnId($transactionID);
        $transaction->setIsClosed($closed);
        $transaction->save();
        $order->save();

    }
    public function setStatusPendingPayment($increment_id) {


        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        if (sizeof($order->getData())==0) {
            $this->log->LogError('Attenzione richiesto ordine '.$increment_id." non trovato");
            return;
        }

        //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
        //$order->save();
        //echo "\nStatus: ".$order->getStatus();
        $order->setData('state', "pending_payment");
        $order->setStatus("pending_payment");
        $history = $order->addStatusHistoryComment('Ordine caricato e da processare.', false);
        $history->setIsCustomerNotified(false);
        $order->save();

    }

    public function setStatusPending($increment_id) {


        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        if (sizeof($order->getData())==0) {
            $this->log->LogError('Attenzione richiesto ordine '.$increment_id." non trovato");
            return;
        }

        //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
        //$order->save();
        //echo "\nStatus: ".$order->getStatus();
        $order->setData('state', "pending");
        $order->setStatus("pending");
        $history = $order->addStatusHistoryComment('Ordine da inviare al magazzino', false);
        $history->setIsCustomerNotified(false);
        $order->save();

    }

    public function setStatusProcessing($increment_id) {


        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        if (sizeof($order->getData())==0) {
            $this->log->LogError('Attenzione richiesto ordine '.$increment_id." non trovato");
            return;
        }

        //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
        //$order->save();
        //echo "\nStatus: ".$order->getStatus();
        $order->setData('state', "processing");
        $order->setStatus("processing");
        $history = $order->addStatusHistoryComment('Ordine inviato al magazzino', false);
        $history->setIsCustomerNotified(false);
        $order->save();

    }

    public function getOrderStatus($dw_order_number) {
        $increment_id = $this->getOrderIdByDWId($dw_order_number);
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        return $order->getStatus();
    }

    public function setStatusOnHold($increment_id) {


        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        if (sizeof($order->getData())==0) {
            $this->log->LogError('Attenzione richiesto ordine '.$increment_id." non trovato");
            return;
        }

        //echo "\nStatus: ".$order->getStatus();

        if ($order->getStatus()=='holded'
            || $order->getStatus()=='complete') return;
        //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
        //$order->save();
        $order->hold()->addStatusHistoryComment("Shipment esito negativo");
        $order->save();
    }

    public function doUnHold($increment_id) {
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $order->unhold()->save();
    }

    /**
     * Vecchia procedura commentata il 27/10/2016 quando è stato introdotto il guest checkout
     * @param $dw_customer_id
     * @param $site
     * @return array|null
     */
    public function _getOrderHistoryByCustomerId($dw_customer_id, $site) {

        $data = Mage::getModel('customer/customer')
            ->getCollection()
            ->addAttributeToSelect('customer_id')
            ->addAttributeToFilter('customer_no',$dw_customer_id)->load()->getData();

        $id_customer = $data[0]['entity_id'];
        $_customer = Mage::getModel('customer/customer')->load($id_customer);

        $data = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToSelect('increment_id')
            ->addAttributeToFilter('customer_email',$_customer->getEmail())->load()->getData();

        $errors = array_filter($data);

        if (empty($errors)) {
            return null; //id non trovato
        }

        $lista = array();
        foreach ($data as $order) {
            $lista[] = $order['increment_id'];
        }

        /*
        if (is_array($data)) {

            return $data[0]['increment_id'];
        } else
            return $data['increment_id'];
        */

        return $lista;

    }

    public function getOrderHistoryByCustomerId($dw_customer_id, $site) {

        $con = OMDBManager::getMagentoConnection();
        $sql ="SELECT increment_id FROM sales_flat_order WHERE dw_customer_id='$dw_customer_id'";
        $res = mysql_query($sql);

        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $lista[] = $row->increment_id;
        }

        /*
        if (is_array($data)) {

            return $data[0]['increment_id'];
        } else
            return $data['increment_id'];
        */

        return $lista;

    }

    public function createFiscalInfo($dw_order_no) {
        $increment_id = $this->getOrderIdByDWId($dw_order_no);
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');

        //$old_status= $order->getData('status');
        //$old_state= $order->getData('state');

        //genera scontrino sempre
        $num_scontrino = CountersHelper::getTrxHeaderId();
        $data_documento = date('d/m/Y');
        $order->setData('bill_number',$num_scontrino);
        $order->setData('bill_date',$data_documento);
        $order->save();

        $billingAddress = Mage::getModel('sales/order_address')->load($order->getBillingAddressId());
        $billing_country = $billingAddress->getData('country_id');

        $country_details = CountryDBHelper::getCountryDetails($billing_country);

        if ($order->getData('needInvoice')=='true' || $country_details->sopra_soglia == '1') {  //RINO 27/07/2016 sospra_soglia==1 // Rino 13/07/2016 sequenza numeratore per paese di billing in soprasoglia

            //$country = $country_details->sopra_soglia=='1' ? $billing_country : 'it';
            $codice_ente = $country_details->codice_ente;
           // $num_fattura=$numero_doc = str_pad(CountersHelper::getInvoiceNumber(date('Y'), $country), 7,'0', STR_PAD_LEFT); //RINO 27/07/2016
            $num_fattura=$numero_doc = str_pad(CountersHelper::getInvoiceNumber(date('Y'), $codice_ente), 7,'0', STR_PAD_LEFT); //RINO 27/07/2016
            $data_documento = date('d/m/Y');
            $order->setData('invoice_number',$num_fattura);
            $order->setData('invoice_date',$data_documento);
            $order->save();
        }

    }

    public function prepareConfirmOrder($dw_order_no, $delivery_id, $flag_tipo_spedizione) {

        $increment_id = $this->getOrderIdByDWId($dw_order_no);
        $con = OMDBManager::getConnection();
        $sql ="INSERT INTO conferma_ordine (increment_id, dw_order_no, delivery_id, flag_tipo_spedizione)
        VALUES ('$increment_id', '$dw_order_no', $delivery_id, $flag_tipo_spedizione)";
        //echo "\nSQL: ".$sql;
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
