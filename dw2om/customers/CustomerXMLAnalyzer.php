<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 11:20
 */


ini_set('memory_limit', '-1');
//error_reporting(E_ERROR );

require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../../common/MagentoHelper.php";
require_once realpath(dirname(__FILE__))."/CustomerObject.php";
require_once realpath(dirname(__FILE__))."/MagentoCustomerHelper.php";

class CustomerXMLAnalyzer {


    public function __construct($file)
    {
        $this->file = $file;
    }

    public function process()
    {
        $fh = fopen($this->file, 'r');
        $buffer = "";
        $counter = 0;
        while(!feof($fh)){
            $line = fgets($fh);
            $counter++;
            if ($counter<=2) {
                continue;
            }

            $str = trim(substr($line,0, strlen($line)-1));
            //$str = $line;

            //echo "\n".$str;
            # do same stuff with the $line
            //echo "\n".substr($line,0, strlen($line)-2);
            //echo "\n!".$str."!";
            if ($str=="</customer>") {
                //echo "\nFOUND";

                $xmlContent =$buffer.$str;
                //print_r($xmlContent);
                $xml = new SimpleXMLElement($xmlContent);


                $this->processCustomerSection($xml);
                //die;
                $buffer ="";
            } else $buffer .= $line;

        }
        fclose($fh);

    }


    private function getCredentials(SimpleXMLElement $xmlElement, CustomerObject &$customerObject){

        $login = $xmlElement->login;
        $customerObject->login = (string)$login;
        $customerObject->email = (string)$login;

    }

    private function getProfile(SimpleXMLElement $xmlElement, CustomerObject &$customerObject){

        $first_name = $xmlElement->{'first-name'};
        $last_name = $xmlElement->{'last-name'};

        $customerObject->first_name = (string)$first_name;
        $customerObject->last_name = (string)$last_name;
    }

    private function getCustomAttributes(SimpleXMLElement $xmlElement, CustomerObject &$customerObject){
        $obj = $xmlElement;
        $contatore = 0;
        foreach($obj->{'custom-attribute'} as $key=>$row){


            $attributo = (string)$row['attribute-id'];
            $valore = (string)$obj->{'custom-attribute'}[$contatore++];
            $customerObject->{$attributo} = $valore;
            //echo "\n$attributo, $valore";

        }
    }

    public function processCustomerSection(SimpleXMLElement $xmlContent = null)
    {
        //$doc = new DOMDocument();
        //$doc->load($this->file);

        $xml = $xmlContent;
        $customer_no = $xml['customer-no'];

        $count = 0;

        $customerObject = new CustomerObject();
        $customerObject->customer_no = (string)$customer_no;

        //estrae le credenziali (mail)
        $credentials = $xml->xpath("credentials")[0];
        $this->getCredentials($credentials, $customerObject);

        //estrae il profilo
        $profile = $xml->xpath("profile")[0];
        $this->getProfile($profile , $customerObject);

        //estrae i custom attributes

        $obj = $xml->xpath("profile/custom-attributes")[0];
        $this->getCustomAttributes($obj, $customerObject);

        //print_r($customerObject);

        //echo "\nNOME: ".$customerObject->first_name;

        //print_r(get_object_vars($customerObject));

        //set storeid from file
        $magentoHelper = new MagentoHelper();
        $customerObject->store_id = $magentoHelper->getStoreIdFromFile($this->file);  //RINO 05/07/2016

        //import in magento
        $helper = new MagentoCustomerHelper();
        $helper->import($customerObject);

    }
}

//$t = new CustomerXMLAnalyzer('/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/customer_export/inbound/20150421105126-customer_cc_it_DW_SG_20150421085004.xml');
//$t->process();