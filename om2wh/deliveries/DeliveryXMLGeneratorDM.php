<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 01/05/15
 * Time: 19:28
 */
require_once realpath(dirname(__FILE__))."/../../common/ConfigManagerDM.php";
require_once realpath(dirname(__FILE__))."/DeliveryObject.php";
require_once realpath(dirname(__FILE__))."/FileGenerator.php";
require_once realpath(dirname(__FILE__))."/../../omdb/ShipmentDBHelper.php";

class DeliveryXMLGeneratorDM extends FileGenerator {


    private $configManager;
    private $subinventory;
    private $lista_delivery_object = array();

    public function __construct($subinventory) {
        parent::__construct();
        $this->configManager = new ConfigManagerDM();
        $this->subinventory = $subinventory;

    }

    public function addDeliveryObject(DeliveryObject $obj) {
        $this->lista_delivery_object[] = $obj;
    }


    public function generatePickListFile($debug = false) {

        //verifico se ci sono item per il primo subinvetory (primo magazzino) e poi anche per il secondo
        $timestamp = date('YmdHis');
        //$file_name = $timestamp."-deliveries_cc_it_SG_WH_".$timestamp.".xml";
        $suffisso = $this->configManager->getProperty("subinventory.".$this->subinventory);
        echo "\nSuffisso: ".$suffisso;
        $file_name = "deliveries_ov_it_SG_WH".$suffisso."_".$timestamp.".xml";
        echo "\nFilename: ".$file_name;
        $directory = $this->configManager->getDeliveryExportOutboundDir();
        $full_name = $directory."/".$file_name;
        if ($debug) {
            $full_name = '/tmp/'.$file_name;
        }
        $this->createFile($full_name);
        $this->writeRecord('<pick_list>');


        $empty = true;
        foreach ($this->lista_delivery_object as $delivery_object) {
            $content = $this->generatePickList($delivery_object);
            $this->writeRecord($content);
            $empty = false;
        }

        $this->writeRecord('</pick_list>');
        $this->closeFile();
        if ($empty) {
            $this->removeFile($full_name); //rimuove file vuoto;
        }

    }

    private function generatePickList(DeliveryObject $delivery_object){
        $xml_lines=array();

        array_push($xml_lines, '<delivery_header>');

        array_push($xml_lines, '<inventory_code>'.$delivery_object->inventory_code.'</inventory_code>');
        array_push($xml_lines, '<storeid>'.$delivery_object->storeid.'</storeid>');
        array_push($xml_lines, '<subinventory>'.$delivery_object->subinventory.'</subinventory>');
        //array_push($xml_lines, '<subinventory>0001</subinventory>'); //RINO 26/07/2016 TODO ripristinare la riga precedente qualora si usi di nuovo la doppia delivery
        array_push($xml_lines, '<brand>'.$delivery_object->brand.'</brand>');
        array_push($xml_lines, '<delivery_type>'.$delivery_object->delivery_type.'</delivery_type>');
        array_push($xml_lines, '<delivery_id>'.$delivery_object->delivery_id.'</delivery_id>');
        array_push($xml_lines, '<delivery_date>'.$delivery_object->delivery_date.'</delivery_date>');
        array_push($xml_lines, '<order_number>'.$delivery_object->order_number.'</order_number>');
        array_push($xml_lines, '<order_date>'.$delivery_object->order_date.'</order_date>');
        array_push($xml_lines, '<payment_method>'.$delivery_object->payment_method.'</payment_method>');

        //BILLING INFO
        array_push($xml_lines, '<bill_to_info>');
        //array_push($xml_lines, '<bill_to_cust_number>'.$delivery_object->customer->customer_no.'</bill_to_cust_number>');

        //if ($delivery_object->customer->sg_user_id)
        //    array_push($xml_lines, '<bill_to_cust_number>'.$delivery_object->customer->sg_user_id.'</bill_to_cust_number>');
        //else
        //    array_push($xml_lines, '<bill_to_cust_number>'.$delivery_object->customer->customer_no.'</bill_to_cust_number>');

        //array_push($xml_lines, '<bill_to_cust_number>'.$delivery_object->customer->customer_no.'</bill_to_cust_number>');  //ALESSANDRO 18/08/2016
        //array_push($xml_lines, '<bill_to_cust_number>'.$delivery_object->customer->entity_id.'</bill_to_cust_number>');  //RINO 05/09/2016
        array_push($xml_lines, '<bill_to_cust_number>'.$delivery_object->customer_no.'</bill_to_cust_number>');  //Vincenzo 27/10/2016

        array_push($xml_lines, '<bill_to_cust_first_name>'.htmlspecialchars(trim($delivery_object->bill_to_info->getFirstname())).'</bill_to_cust_first_name>');
        array_push($xml_lines, '<bill_to_cust_last_name>'.htmlspecialchars(trim($delivery_object->bill_to_info->getLastname())).'</bill_to_cust_last_name>');
        array_push($xml_lines, '<bill_to_address1>'.htmlspecialchars($delivery_object->bill_to_info->getStreet(1)).'</bill_to_address1>');
        array_push($xml_lines, '<bill_to_address2></bill_to_address2>');
        array_push($xml_lines, '<bill_to_city>'.$delivery_object->bill_to_info->getCity().'</bill_to_city>');
        array_push($xml_lines, '<bill_to_zip_code>'.$delivery_object->bill_to_info->getPostcode().'</bill_to_zip_code>');
        array_push($xml_lines, '<bill_to_province>'.$delivery_object->bill_to_info->getRegion().'</bill_to_province>');
        array_push($xml_lines, '<bill_to_country>'.$delivery_object->bill_to_info->getCountryId().'</bill_to_country>');
        array_push($xml_lines, '<bill_to_telephone>'.$delivery_object->bill_to_info->getTelephone().'</bill_to_telephone>');
        //array_push($xml_lines, '<bill_to_email>'.$delivery_object->customer->email.'</bill_to_email>'); //27/10/2016
        array_push($xml_lines, '<bill_to_email>'.$delivery_object->customer_email.'</bill_to_email>'); //27/10/2016
        array_push($xml_lines, '</bill_to_info>');

        //SHIPPING INFO
        $shippingDBHelper = new ShipmentDBHelper($delivery_object->order_number_not_trimmed);
        $shipping_custom_attributes = $shippingDBHelper->getCustomAttributes();

        if ($shipping_custom_attributes['clickAndCollectStoreId']) {
            //si tratta di click&collect
            //$infoOrdine->click_collect = true;
            array_push($xml_lines, '<ship_to_info>');

                $helper = new MagentoOrderHelper();
                $increment_id = $helper->getOrderIdByDWId($delivery_object->order_number_not_trimmed);
                $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
                //print_r($order->getData());
                $firstName=$order->getData('customer_name');
                $lastName='.';

            //array_push($xml_lines, '<ship_to_cust_number>'.$delivery_object->customer->customer_no.'</ship_to_cust_number>'); //RINO 05/09/2016
            //array_push($xml_lines, '<ship_to_cust_number>'.$delivery_object->customer->entity_id.'</ship_to_cust_number>'); //RINO 05/09/2016
            array_push($xml_lines, '<ship_to_cust_number>'.$delivery_object->customer_no.'</ship_to_cust_number>'); //Vincenzo 27/10/2016
            array_push($xml_lines, '<ship_to_cust_first_name>'.
                htmlspecialchars($shipping_custom_attributes['clickAndCollectStoreName']).
                ' c.a. '.
                htmlspecialchars(trim($firstName)).'</ship_to_cust_first_name>');  //RINO 13/07/2016

            array_push($xml_lines, '<ship_to_cust_last_name>'.htmlspecialchars(trim($lastName)).'</ship_to_cust_last_name>');

            array_push($xml_lines, '<ship_to_address1>'.htmlspecialchars($shipping_custom_attributes['clickAndCollectAddress1']).'</ship_to_address1>');
            array_push($xml_lines, '<ship_to_address2>'.htmlspecialchars($shipping_custom_attributes['clickAndCollectAddress2']).'</ship_to_address2>');
            array_push($xml_lines, '<ship_to_city>'.$shipping_custom_attributes['clickAndCollectCity'].'</ship_to_city>');
            array_push($xml_lines, '<ship_to_zip_code>'.$shipping_custom_attributes['clickAndCollectPostalCode'].'</ship_to_zip_code>');
            array_push($xml_lines, '<ship_to_province>'.$shipping_custom_attributes['clickAndCollectStateCode'].'</ship_to_province>');
            array_push($xml_lines, '<ship_to_country>'.$shipping_custom_attributes['clickAndCollectCountryCode'].'</ship_to_country>');
            array_push($xml_lines, '<ship_to_telephone>'.$delivery_object->ship_to_info->getTelephone().'</ship_to_telephone>');
            array_push($xml_lines, '<ship_to_email></ship_to_email>');
            array_push($xml_lines, '</ship_to_info>');


        } else {
            array_push($xml_lines, '<ship_to_info>');
            //array_push($xml_lines, '<ship_to_cust_number>'.$delivery_object->customer->customer_no.'</ship_to_cust_number>'); //RINO 05/09/2016
            //array_push($xml_lines, '<ship_to_cust_number>'.$delivery_object->customer->entity_id.'</ship_to_cust_number>'); //RINO 05/09/2016
            array_push($xml_lines, '<ship_to_cust_number>'.$delivery_object->customer_no.'</ship_to_cust_number>'); //Vincenzo 27/10/2016

            array_push($xml_lines, '<ship_to_cust_first_name>'.htmlspecialchars(trim($delivery_object->ship_to_info->getFirstname())).'</ship_to_cust_first_name>');
            array_push($xml_lines, '<ship_to_cust_last_name>'.htmlspecialchars(trim($delivery_object->ship_to_info->getLastname())).'</ship_to_cust_last_name>');
            array_push($xml_lines, '<ship_to_address1>'.htmlspecialchars($delivery_object->ship_to_info->getStreet(1)).'</ship_to_address1>');
            array_push($xml_lines, '<ship_to_address2>'.htmlspecialchars($delivery_object->ship_to_info->getNote()).'</ship_to_address2>');
            array_push($xml_lines, '<ship_to_city>'.$delivery_object->ship_to_info->getCity().'</ship_to_city>');
            array_push($xml_lines, '<ship_to_zip_code>'.$delivery_object->ship_to_info->getPostcode().'</ship_to_zip_code>');
            array_push($xml_lines, '<ship_to_province>'.$delivery_object->ship_to_info->getRegion().'</ship_to_province>');
            array_push($xml_lines, '<ship_to_country>'.$delivery_object->ship_to_info->getCountryId().'</ship_to_country>');
            array_push($xml_lines, '<ship_to_telephone>'.$delivery_object->ship_to_info->getTelephone().'</ship_to_telephone>');
            //array_push($xml_lines, '<ship_to_email>'.$delivery_object->customer->email.'</ship_to_email>');
            array_push($xml_lines, '<ship_to_email></ship_to_email>');
            array_push($xml_lines, '</ship_to_info>');
        }

        array_push($xml_lines, '<carrier>'.$delivery_object->carrier.'</carrier>');
        array_push($xml_lines, '<shipping_service>'.$delivery_object->shipping_service.'</shipping_service>');
        //array_push($xml_lines, '<tipo_abitazione>'.$delivery_object->ship_to_info->getData('assemblydeliverypropertytype').'</tipo_abitazione>');
        //array_push($xml_lines, '<piano>'.$delivery_object->ship_to_info->getData('assemblydeliveryfloor').'</piano>');
        //array_push($xml_lines, '<ascensore>'.($delivery_object->ship_to_info->getData('assemblydeliverylift') ? 'true':'').'</ascensore>');
        array_push($xml_lines, '<shipping_note>'.$delivery_object->shipping_note.'</shipping_note>');
        array_push($xml_lines, '<gift_message>'.$delivery_object->gift_message.'</gift_message>');
        array_push($xml_lines, '<ddt_lang>'.$delivery_object->ddt_lang.'</ddt_lang>');


        //DELIVERY LINES
        //array_push($xml_lines, '<delivery_line>');
        foreach ($delivery_object->delivery_lines as $line) {
            array_push($xml_lines, '<delivery_line>');
            array_push($xml_lines, '<delivery_line_id>'.$line->delivery_line_id.'</delivery_line_id>');
            array_push($xml_lines, '<sku>'.$line->sku.'</sku>');
            array_push($xml_lines, '<sku_description>'.$line->sku_description.'</sku_description>');
            array_push($xml_lines, '<qty>'.$line->qty.'</qty>');
            //array_push($xml_lines, '<montaggio>'.$line->montaggio.'</montaggio>');
            array_push($xml_lines, '</delivery_line>');
        }
       // array_push($xml_lines, '</delivery_line>');

        //modifiza richiesta da Zennaro il 07072015
        array_push($xml_lines, '<valuta>EUR</valuta>');
        //array_push($xml_lines, '<pay_amount>'.$delivery_object->pay_amount.'</pay_amount>');
        //array_push($xml_lines, '<pay_amount>'.str_replace(',','', $delivery_object->pay_amount ).'</pay_amount>'); //modificato togliendo il "," su richiesta di zennaro del 08/03/2016
        array_push($xml_lines, '<pay_amount>'.str_replace(',','', number_format($delivery_object->totale_ordine_ripartito,2) ).'</pay_amount>'); //05012017 modifica per number_format

        array_push($xml_lines, '</delivery_header>');


        $this->lines = $xml_lines;

        return $xml_lines;
    }



} 