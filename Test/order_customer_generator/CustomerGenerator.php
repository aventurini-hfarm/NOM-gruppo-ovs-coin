<?php

class CustomerGenerator
{
    protected $_defaultData = array (
        'account' => array(
            'website_id' => '1',
            'group_id' => '1',
            'prefix' => '',
            'firstname' => 'Firstname{id}',
            'middlename' => '',
            'lastname' => 'Lastname',
            'suffix' => '',
            'email' => 'email{id}@example.net',
            'dob' => '',
            'taxvat' => '',
            'gender' => '',
            'sendemail_store_id' => '1',
            'password' => 'a111111',
            'default_billing' => '_item1',
            'default_shipping' => '_item1',
        ),
        'address' => array(
            '_item1' => array(
                'prefix' => '',
                'firstname' => 'Firstname',
                'middlename' => '',
                'lastname' => 'Lastname',
                'suffix' => '',
                'company' => '',
                'street' => array(
                    0 => 'Address',
                    1 => '',
                ),
                'city' => 'City',
                'country_id' => 'US',
                'region_id' => '12',
                'region' => '',
                'postcode' => '123123',
                'telephone' => '123123123',
                'fax' => '',
                'vat_id' => '',
            ),
        ),
    );

    /**
     * @var Mage_Core_Model_Resource_Resource $_resource
     */
    protected $_resource;
    /**
     * @var Varien_Db_Adapter_Interface $_adapter
     */
    protected $_adapter;

    public function __construct()
    {
        $this->_resource = Mage::getResourceSingleton('core/resource');
        $this->_adapter = $this->_resource->getReadConnection();
    }

    protected function _processTemplates(&$data)
    {
        $config = $this->_adapter->getConfig();

        $select = $this->_adapter->select();
        $select
            ->from('information_schema.tables', 'AUTO_INCREMENT')
            ->where('table_schema = ?', $config['dbname'])
            ->where(
                'table_name = ?',
                $this->_adapter->getTableName('customer_entity')
            );

        $nextId = $this->_adapter->fetchOne($select);

        foreach ($data['account'] as &$field){
            $field = str_replace('{id}', $nextId, $field);
        }

        foreach ($data['address'] as &$address) {
            foreach ($address as &$field) {
                $field = str_replace('{id}', $nextId, $field);
            }
        }
    }

    public function createCustomer($data = array())
    {
        $data = array_replace_recursive($this->_defaultData, $data);

        $this->_processTemplates($data);

        /** @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer');

        $customer->setData($data['account']);

        foreach (array_keys($data['address']) as $index) {
            $address = Mage::getModel('customer/address');

            $addressData = array_merge($data['account'], $data['address'][$index]);

            // Set default billing and shipping flags to address
            $isDefaultBilling = isset($data['account']['default_billing'])
                && $data['account']['default_billing'] == $index;
            $address->setIsDefaultBilling($isDefaultBilling);
            $isDefaultShipping = isset($data['account']['default_shipping'])
                && $data['account']['default_shipping'] == $index;
            $address->setIsDefaultShipping($isDefaultShipping);

            $address->addData($addressData);

            // Set post_index for detect default billing and shipping addresses
            $address->setPostIndex($index);

            $customer->addAddress($address);
        }

        // Default billing and shipping
        if (isset($data['account']['default_billing'])) {
            $customer->setData('default_billing', $data['account']['default_billing']);
        }
        if (isset($data['account']['default_shipping'])) {
            $customer->setData('default_shipping', $data['account']['default_shipping']);
        }
        if (isset($data['account']['confirmation'])) {
            $customer->setData('confirmation', $data['account']['confirmation']);
        }

        if (isset($data['account']['sendemail_store_id'])) {
            $customer->setSendemailStoreId($data['account']['sendemail_store_id']);
        }

        $customer
            ->setPassword($data['account']['password'])
            ->setForceConfirmed(true)
            ->save()
            ->cleanAllAddresses()
        ;

        return $customer;
    }
}