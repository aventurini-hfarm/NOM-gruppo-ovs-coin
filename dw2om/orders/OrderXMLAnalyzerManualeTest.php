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
//require_once "MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__))."/OrderObject.php";
require_once realpath(dirname(__FILE__))."/ItemObject.php";
require_once realpath(dirname(__FILE__))."/ShippingObject.php";
require_once realpath(dirname(__FILE__))."/ShipmentObject.php";
require_once realpath(dirname(__FILE__))."/TotalsObject.php";
require_once realpath(dirname(__FILE__))."/PaymentObject.php";
require_once realpath(dirname(__FILE__))."/../customers/CustomerObject.php";
require_once realpath(dirname(__FILE__))."/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__)) . "/../../omdb/OrderDBHelper.php";
require_once realpath(dirname(__FILE__)) . "/../../omdb/PaymentDBHelper.php";
require_once realpath(dirname(__FILE__)) . "/../../omdb/ShipmentDBHelper.php";

class OrderXMLAnalyzerManualeTest {


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
            //echo "\nLine: ".$line;
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
            if ($str=="</order>") {
                //echo "\nFOUND";

                $xmlContent =$buffer.$str;
                //print_r($xmlContent);
                $xml = new SimpleXMLElement($xmlContent);


                $this->processOrderSection($xml);
                //die;
                $buffer ="";
            } else $buffer .= $line;

        }
        fclose($fh);

    }


    private function getCustomerObject(SimpleXMLElement $xmlContent = null) {
        $xml = $xmlContent;
        $customer = new CustomerObject();

        $customer_no = $xml->{'customer-no'};
        $customer->customer_no  = (string)$customer_no;

        $customer_name = $xml->{'customer-name'};
        $customer->customer_name  = (string)$customer_name;

        $customer_email = $xml->{'customer-email'};
        $customer->customer_email  = (string)$customer_email;

        $billingXml = $xml->xpath("billing-address")[0];

        $field = $billingXml->{'first-name'};
        $customer->billing_first_name = (string)$field;

        $field = $billingXml->{'last-name'};
        $customer->billing_last_name = (string)$field;

        $field = $billingXml->{'address1'};
        $customer->billing_address1 = (string)$field;

        $field = $billingXml->{'address2'};
        $customer->billing_address2 = (string)$field;

        $field = $billingXml->{'city'};
        $customer->billing_city = (string)$field;

        $field = $billingXml->{'postal-code'};
        $customer->billing_postal_code = (string)$field;

        $field = $billingXml->{'state-code'};
        $customer->billing_state_code = (string)$field;

        $field = $billingXml->{'country-code'};
        $customer->billing_country_code = (string)$field;

        $field = $billingXml->{'phone'};
        $customer->billing_phone = (string)$field;

        return $customer;
    }


    private function getItems($lista_nodi) {

        //$xml = $xmlContent;
        $lista = array();
        foreach ($lista_nodi as $nodo) {
            $xml = $nodo;
            $item = new ItemObject();
            $item->net_price = (string)$xml->{'net-price'};
            $item->tax = (string)$xml->{'tax'};
            $item->gross_price = (string)$xml->{'gross-price'};
            $item->base_price = (string)$xml->{'base-price'};
            $item->lineitem_text = (string)$xml->{'lineitem-text'};
            $item->tax_basis = (string)$xml->{'tax-basis'};
            $item->position = (string)$xml->{'position'};
            $item->product_id = (string)$xml->{'product-id'};
            $item->product_name = (string)$xml->{'product-name'};
            $item->quantity = (string)$xml->{'quantity'};
            $item->tax_rate = (string)$xml->{'tax-rate'};
            $item->shipment_id = (string)$xml->{'shipment-id'};
            $item->gift = (string)$xml->{'gift'};

            //verifica se ci sono sconti
            $tmpObj = $xml->xpath("price-adjustments/price-adjustment")[0];
            $item->promotion_id = (String)$tmpObj->{'promotion-id'};
            $item->campaign_id = (String)$tmpObj->{'campaign-id'};
            $item->discount_gross_price = (String)$tmpObj->{'gross-price'};

            //verifica se ci sono options

            $lista_options = $xml->xpath("option-lineitems/option-lineitem");
            $item_lineoptions = array();
            foreach ($lista_options as $option) {
                $itemOption = new stdClass();
                $itemOption->net_price = (string)$option->{'net-price'};
                $itemOption->tax = (string)$option->{'tax'};
                $itemOption->gross_price = (string)$option->{'gross-price'};
                $itemOption->base_price = (string)$option->{'base-price'};
                $itemOption->lineitem_text = (string)$option->{'lineitem-text'};
                $itemOption->tax_basis = (string)$option->{'tax-basis'};
                $itemOption->option_id = (string)$option->{'option-id'};
                $itemOption->value_id = (string)$option->{'value-id'};
                $itemOption->product_id = (string)$option->{'product-id'};

                array_push ($item_lineoptions, $itemOption);
            }

            $tmpAr = array_filter($item_lineoptions);
            if (!empty($tmpAr)) {
                $item->has_options = true;
                $item->lineoptions = $item_lineoptions;
            } else
                $item->has_options = false;

            $lista[] = $item;
        }
        //print_r($lista);
        return $lista;

    }

    private function getShipping(SimpleXMLElement $xmlContent = null) {

        $xml = $xmlContent;
            $item = new ShippingObject();
            $item->net_price = (string)$xml->{'net-price'};
            $item->tax = (string)$xml->{'tax'};
            $item->gross_price = (string)$xml->{'gross-price'};
            $item->base_price = (string)$xml->{'base-price'};
            $item->lineitem_text = (string)$xml->{'lineitem-text'};
            $item->tax_basis = (string)$xml->{'tax-basis'};
            $item->item_id = (string)$xml->{'item-id'};
            $item->shipment_id = (string)$xml->{'shipment-id'};
            $item->tax_rate = (string)$xml->{'tax-rate'};

        //verifica se ci sono sconti
        $tmpObj = $xml->xpath("price-adjustments/price-adjustment")[0];
        $item->promotion_id = (String)$tmpObj->{'promotion-id'};
        $item->campaign_id = (String)$tmpObj->{'campaign-id'};
        $item->discount_gross_price = (String)$tmpObj->{'gross-price'};


        //print_r($item);
        return $item;

    }

    private function getShipment(SimpleXMLElement $xmlContent = null) {

        $xml = $xmlContent;
        $obj = $xmlContent;

        $item = new ShipmentObject();
        $item->shippment_id = (string)$xml['shipment-id'];
        $item->shipping_method = (string)$xml->{'shipping-method'};

        $shippingXml = $xml->xpath("shipping-address")[0];

        $field = $shippingXml->{'first-name'};
        $item->shipping_first_name = (string)$field;

        $field = $shippingXml->{'last-name'};
        $item->shipping_last_name = (string)$field;

        $field = $shippingXml->{'address1'};
        $item->shipping_address1 = (string)$field;

        $field = $shippingXml->{'address2'};
        $item->shipping_address2 = (string)$field;

        $field = $shippingXml->{'city'};
        $item->shipping_city = (string)$field;

        $field = $shippingXml->{'postal-code'};
        $item->shipping_postal_code = (string)$field;

        $field = $shippingXml->{'state-code'};
        $item->shipping_state_code = (string)$field;

        $field = $shippingXml->{'country-code'};
        $item->shipping_country_code = (string)$field;

        $field = $shippingXml->{'phone'};
        $item->shipping_phone = (string)$field;

        $customFields = $xml->xpath("custom-attributes")[0]->{'custom-attribute'};

        foreach ($customFields as $key=>$value) {
            if ('assemblyDeliveryFloor'==$key) {
                $item->assemblyDeliveryFloor = (string)$value;
            }
            if ('assemblyDeliveryLift'==$key) {
                $item->assemblyDeliveryLift = (string)$value;
            }
            if ('assemblyDeliveryPropertyType'==$key) {
                $item->assemblyDeliveryPropertyType = (string)$value;
            }
            if ('assemblyDeliveryNote'==$key) {
                $item->assemblyDeliveryNote = (string)$value;
            }

        }


        $contatore = 0;
        $lista = array();
        foreach($obj->xpath("custom-attributes")[0]->{'custom-attribute'} as $key=>$row){


            $attributo = (string)$row['attribute-id'];
            $valore = (string)$obj->xpath("custom-attributes")[0]->{'custom-attribute'}[$contatore++];

            $lista[$attributo] = $valore;

            //echo "\n$attributo, $valore";

        }

        $item->custom_attributes = $lista;
        //print_r($item);
        return $item;

    }

    private function getTotals(SimpleXMLElement $xmlContent = null) {

        $xml = $xmlContent;
        $item = new TotalsObject();

        $obj = $xml->xpath("merchandize-total")[0];
        //TODO ci possono essere più price-adjustment con più promo

        $tmpObjs = $obj->xpath("price-adjustments/price-adjustment");
        $lista_valori = array();
        foreach ($tmpObjs as $tmpObj) {
        //$tmpObj = $obj->xpath("price-adjustments/price-adjustment")[0];
            $values = array( 'net-price'=>(string)$obj->{'net-price'},
                'tax'=>(string)$obj->{'tax'}, 'gross-price'=>(string)$obj->{'gross-price'},
            'promotion-id'=>(String)$tmpObj->{'promotion-id'}, 'campaign-id'=>(String)$tmpObj->{'campaign-id'},
                'discount-gross-price'=>(String)$tmpObj->{'gross-price'});

            $lista_valori[] = $values;

        }
        $item->merchandize_total = $lista_valori;



        $obj = $xml->xpath("adjusted-merchandize-total")[0];
        $values = array( 'net-price'=>(string)$obj->{'net-price'},
            'tax'=>(string)$obj->{'tax'}, 'gross-price'=>(string)$obj->{'gross-price'});
        $item->adjusted_merchandize_total = $values;

        $obj = $xml->xpath("shipping-total")[0];
        $values = array( 'net-price'=>(string)$obj->{'net-price'},
            'tax'=>(string)$obj->{'tax'}, 'gross-price'=>(string)$obj->{'gross-price'});
        $item->shipping_total = $values;

        $obj = $xml->xpath("adjusted-shipping-total")[0];
        $values = array( 'net-price'=>(string)$obj->{'net-price'},
            'tax'=>(string)$obj->{'tax'}, 'gross-price'=>(string)$obj->{'gross-price'});
        $item->adjusted_shipping_total = $values;

        $obj = $xml->xpath("order-total")[0];
        $values = array( 'net-price'=>(string)$obj->{'net-price'},
            'tax'=>(string)$obj->{'tax'}, 'gross-price'=>(string)$obj->{'gross-price'});
        $item->order_total = $values;

        //print_r($item);

        return $item;

    }

    private function getPayment(SimpleXMLElement $xmlContent = null) {
        $xml = $xmlContent;
        print "XML ".$xml;
        $credit_card = $xml->xpath("credit-card")[0];
        $custom_method = $xml->xpath("custom-method")[0];

        $paymentObj = new PaymentObject();
        echo "\nCustom: ".$custom_method;

        if ($custom_method) {
            $method_name = (string)$custom_method->{'method-name'};
            if ($method_name=="Contrassegno") {
                $paymentObj->type="CONTRASSEGNO";
                $paymentObj->amount = (string)$xml->{'amount'};
                return $paymentObj;
            }
        }

        if ($credit_card) {//CYBERSOURCE
            $paymentObj->type="CYBERSOURCE";
            $paymentObj->card_type = (string)$credit_card->{'card-type'};
            $paymentObj->card_number =(string) $credit_card->{'card-number'};
            $paymentObj->card_holder = (string)$credit_card->{'card-holder'};
            $paymentObj->expiration_month =(string) $credit_card->{'expiration-month'};
            $paymentObj->expiration_year = (string)$credit_card->{'expiration-year'};

            $paymentObj->amount = (string)$xml->{'amount'};
            $paymentObj->transaction_id =(string) $xml->{'transaction-id'};

            $customFields = $xml->xpath("custom-attributes")[0]->{'custom-attribute'};
            $value = $customFields['0'];
            $paymentObj->approvalStatus = (string)$value;

            $value = $customFields['1'];
            $paymentObj->authAmount = (string)$value;

            $value = $customFields['2'];
            $paymentObj->authCode = (string)$value;

            $value = $customFields['3'];
            $paymentObj->cardType = (string)$value;

            $value = $customFields['4'];
            $paymentObj->requestId = (string)$value;

            $value = $customFields['5'];
            $paymentObj->requestToken = (string)$value;

            $value = $customFields['6'];
            $paymentObj->subscriptionID = (string)$value;

        } else { //PAYPAL
            $paymentObj->type="PAYPAL";
            $paymentObj->amount = (string)$xml->{'amount'};
            $paymentObj->transaction_id =(string) $xml->{'transaction-id'};
            $customFields = $xml->xpath("custom-attributes")[0]->{'custom-attribute'};
            $value = $customFields['6'];
            $paymentObj->requestToken = (string)$value;

        }

        return $paymentObj;
    }

    private function getCustomAttributes(SimpleXMLElement $xmlElement){
        $obj = $xmlElement;
        $contatore = 0;
        $lista = array();
        foreach($obj->{'custom-attribute'} as $key=>$row){


            $attributo = (string)$row['attribute-id'];
            $valore = (string)$obj->{'custom-attribute'}[$contatore++];
            $lista[$attributo] = $valore;

            //echo "\n$attributo, $valore";

        }

        return $lista;
    }

    public function processOrderSection(SimpleXMLElement $xmlContent = null)
    {
        //$doc = new DOMDocument();
        //$doc->load($this->file);

        $xml = $xmlContent;
        $order = new OrderObject();

        $order_no = $xml['order-no'];
        $order->order_no = (string)$order_no;

        $order_date = $xml->{'order-date'};
        $order->order_date = (string)$order_date;

        $currency = $xml->currency;
        $order->currency = (string)$currency;

        $customer_locale = $xml->{'customer-locale'};
        $order->customer_locale = (string)$customer_locale;

        $customerObj = $this->getCustomerObject($xml->xpath('customer')[0]);
        $order->customer = $customerObj;

        $lista_prodotti = $this->getItems( $xml->xpath("product-lineitems/product-lineitem") );
        $order->lista_prodotti = $lista_prodotti;


        $shipping = $this->getShipping( $xml->xpath("shipping-lineitems/shipping-lineitem")[0] );
        $order->shipping = $shipping;

        $shipment = $this->getShipment($xml->xpath('shipments/shipment')[0]);
        $order->shipment = $shipment;


        //TOTALS
        $totalsObj = $this->getTotals($xml->xpath('totals')[0]);
        $order->totals = $totalsObj;


        //PAYMENT
        $payment = $this->getPayment($xml->xpath('payments/payment')[0]);
        $order->payment = $payment;


        //custom attributes:
        $customFields = $this->getCustomAttributes($xml->xpath("custom-attributes")[0]);
        $order->custom_attributes = $customFields;

       // print_r($customFields);

        foreach ($customFields as $key=>$value) {
            if ('needInvoice'==$key) {
                $order->need_invoice = (string)$value;
                break;
            }
        }

        if ($this->isClickAndCollect($order))
            $order->store_code_pick = $this->getClickAndCollectStoreId($order);
        else
            $order->store_code_pick = null;

        print_r($order);

        //aggiunge le info addizionali sul DB

       //$this->addShippingPromoToDb($order);
       //$this->addMerchandizePromoToDb($order);
       //$this->addPaymentToDb($order);
      //$this->addOrderCustomAttributes($order);
       $this->addShipmentCustomAttributes($order);
      //  $this->addItemOptions($order);

       //aggiunge ordine su Magento
      // $helper = new MagentoOrderHelper();
      // $helper->setCustomerDWCode($order->customer);
      // $helper->createOrder($order);

        //get order id
      //  $increment_id = $helper->getOrderIdByDWId($order->order_no);
        //$helper->setStatusPending($increment_id);
      //  $helper->setStatusProcessing($increment_id);



    }

    private function getClickAndCollectStoreId(OrderObject $order) {
        $customFields=$order->shipment->custom_attributes;
        return $customFields['clickAndCollectStoreId'];
    }

    private function isClickAndCollect(OrderObject $order) {
        return ($order->shipment->shipping_method == 'ClickAndCollect');
    }

    private function addShipmentCustomAttributes(OrderObject $order){
        //echo "\nAdding Custom Attributes";
        $customFields=$order->shipment->custom_attributes;

        print_r($customFields);
        //$orderDbHelper = new ShipmentDBHelper($order->order_no);
        //$orderDbHelper->addCustomAttributes($customFields);


    }

    private function addOrderCustomAttributes(OrderObject $order){


        $customFields=$order->custom_attributes;


        $orderDbHelper = new OrderDBHelper($order->order_no);
        $orderDbHelper->addCustomAttributes($customFields);


    }


    private function addShippingPromoToDb(OrderObject $order) {

        $shipping = $order->shipping;
        if ($shipping->promotion_id) {
            //inserisce i dati addizionali sul DB
            $orderDbHelper = new OrderDBHelper($order->order_no);
            $orderDbHelper->addShippingPromotion($shipping->promotion_id, $shipping->campaign_id, $shipping->discount_gross_price);
        }
    }

    private function addPaymentToDb(OrderObject $order) {

        $payment = $order->payment;
        if ($payment->type=="CYBERSOURCE") {
            //inserisce i dati addizionali sul DB
            $paymentHelper = new PaymentDBHelper($order->order_no);
            $paymentHelper->addPaymentInfo($payment->type, $order->order_no, $payment->requestId,
            $payment->requestToken, $payment->amount);
        }

        if ($payment->type=="PAYPAL") {
            //inserisce i dati addizionali sul DB
            $paymentHelper = new PaymentDBHelper($order->order_no);
            $paymentHelper->addPaymentInfo($payment->type, $order->order_no, $payment->transaction_id,
                $payment->requestToken, $payment->amount);
        }

    }

    private function addMerchandizePromoToDb(OrderObject $order) {

        $merchandize_total = $order->totals->merchandize_total;
        $orderDbHelper = new OrderDBHelper($order->order_no);
        $orderDbHelper->resetMerchandizePromotion();
        foreach ($merchandize_total as $item) {
            if ($item['promotion-id']) {
                //inserisce i dati addizionali sul DB

                $orderDbHelper->addMerchandizePromotion($item['promotion-id'], $item['campaign-id'],
                    $item['discount-gross-price']);
            }
        }
    }

    private function addItemOptions(OrderObject $order) {

        $orderDbHelper = new OrderDBHelper($order->order_no);
        $items = $order->lista_prodotti;
        foreach ($items as $item) {

            if ($item->has_options) {
                $line_options = $item->lineoptions;

                foreach ($line_options as $option) {
                    //print_r($option);
                    $tmp_list = array();

                    $record = new stdClass();
                    $record->key = 'base-price';
                    $record->value = $option->base_price;
                    array_push($tmp_list, $record);

                    $record = new stdClass();
                    $record->key = 'lineitem-text';
                    $record->value = $option->lineitem_text;
                    array_push($tmp_list, $record);

                    $record = new stdClass();
                    $record->key = 'option-id';
                    $record->value = $option->option_id;
                    array_push($tmp_list, $record);

                    $record = new stdClass();
                    $record->key = 'value-id';
                    $record->value = $option->value_id;
                    array_push($tmp_list, $record);

                    $record = new stdClass();
                    $record->key = 'product-id';
                    $record->value = $option->product_id;
                    array_push($tmp_list, $record);

                    $orderDbHelper->addItemOptions($item->product_id,$tmp_list );
                }
            }
        }
    }
}

//$t = new OrderXMLAnalyzerManualeTest('/tmp/test_cc_order.xml');
$t = new OrderXMLAnalyzerManualeTest('/home/OrderManagement/testFiles/order_export/archive/order_cc_it_DW_SG_20151014083501.xml');
//$t = new OrderXMLAnalyzer('/Users/vincenzosambucaro/PhpstormProjects/OrderManagement/testFiles/order_export/inbound/20150417113622-order_cc_it_DW_SG_20150417093503.xml');

$t->process();