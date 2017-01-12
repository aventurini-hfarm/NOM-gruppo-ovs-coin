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

class FixCustomerNo {

    private $con;
    public function process($lista_coppie) {
        $this->con = OMDBManager::getMagentoConnection();
        foreach ($lista_coppie as $coppia) {
            $dw_order_number = substr(trim($coppia[0]),0,8);
            $customer_no = $coppia[1];
            echo "\nDw_order:".$dw_order_number.", customer_no: ".$customer_no;
            $this->setCustomerDWCode($dw_order_number, $customer_no);
        }
        OMDBManager::closeConnection($this->con);
    }

    public function processFixPadding() {
        $this->con = OMDBManager::getMagentoConnection();
        $this->fixPadding();
        OMDBManager::closeConnection($this->con);
    }

    public function fixPadding() {
        $sql ="SELECT * FROM customer_entity_varchar where attribute_id=154 and not value like '0%' and length(value)<8";
        $res = mysql_query($sql, $this->con);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $entity_id = $row->entity_id;
            $value = $row->value;
            $new_value = str_pad($value,8,'0',STR_PAD_LEFT);
            $lista[] = array($entity_id ,  $new_value);
            echo "\nEntity: ".$entity_id." , new_value: ".$new_value;
        }

        foreach ($lista as $item) {
            $entity_id = $item[0];
            $value = $item[1];
            $sql ="UPDATE customer_entity_varchar SET value='$value' WHERE entity_id=$entity_id AND attribute_id=154";
            mysql_query($sql);
        }


    }

    public function setCustomerDWCode($dw_order_number, $customer_no)
    {



        //echo "\nConnessione";

        $sql = "SELECT entity_id FROM customer_entity_varchar WHERE attribute_id=154 AND value='$customer_no'";
        //echo "\nSQL:".$sql;
        $res = mysql_query($sql, $this->con);
        $id_customer = null;
        while ($row = mysql_fetch_object($res)) {
            $id_customer = $row->entity_id;
        }

        if (!$id_customer) {
            echo "\nAttenzione customer_no: ".$customer_no. " per ordine: ".$dw_order_number." non esiste";
            return;
        }

        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->load($id_customer);
        //print_r($customer);
        $email = $customer->email;
        $nome = $customer->firstname;
        $cognome = $customer->lastname;

        $sql ="UPDATE sales_flat_order SET customer_id=$id_customer , customer_email='$email', customer_firstname='$nome',
        customer_lastname='$cognome'
        WHERE dw_order_number='$dw_order_number'";

        //echo "\nSQL: ".$sql;
        mysql_query($sql, $this->con);


        }

}


$lista = array(
    array('00292658','00819977'),
    array('00293703','00138803'),
    array('00293679','00821974'),
    array('00292658','00819977'),
    array('00289038','00816344'),
    array('00287821','00793373'),
    array('00286920','00021883'),
    array('00286167','00773492')

);

//$lista = array(array('00294061 ','00037291'));

$t = new FixCustomerNo();
$t->process($lista);
//$t->processFixPadding();
