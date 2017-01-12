<?php
// Include required library files.
require_once realpath(dirname(__FILE__)).'/paypal-config.php';
require_once realpath(dirname(__FILE__)).'/../paypal-php-library-dev/autoload.php';


class PayPalProcessor {

    private $sandbox = TRUE;
    private $api_version = '119.0'; // Released 11.05.2014

    //Rino 15/07/2016 -  USARE PER SVILUPPO
    //private $api_username = 'merchant.dev_api1.gruppocoin.com';
    //private $api_password = '1368430698';
    //private $api_signature = 'ACG1CljFnYJ6mJi3ile-OTDYAjlpAqwPmySg34yPOXHksp-ZK9IZZL8o';
    //Paypal Web Service Endpoint: http://api-aa-3t.sandbox.paypal.com/2.0/

    //Rino 15/07/2016 - USARE PER PRODUZIONE
    private $api_username = 'andrea.rizzo_api1.gruppocoin.it';
    private $api_password = 'XM5Z78C6QEFGQDC3';
    private $api_signature = 'Aftta8cMEbW0ae-56ergoWB2rKnvAC7ekoLERsDVaE2oZD0ol-UJ4Dyk';
    // Paypal Web Service Endpoint: https://api-aa-3t.paypal.com/2.0/

    private $print_headers = false;
    private $log_results = false;
    private $log_path = '/tmp/';

    public function doCapture($transactionId, $amount) {

        // Create PayPal object.
        $PayPalConfig = array(
            'Sandbox' => $this->sandbox,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'PrintHeaders' => $this->print_headers,
            'LogResults' => $this->log_results,
            'LogPath' => $this->log_path,
        );

        $PayPal = new angelleye\PayPal\PayPal($PayPalConfig);

// Prepare request arrays
        $DCFields = array(
            'authorizationid' => $transactionId, 				// Required. The authorization identification number of the payment you want to capture. This is the transaction ID returned from DoExpressCheckoutPayment or DoDirectPayment.
            'amt' => $amount, 							// Required. Must have two decimal places.  Decimal separator must be a period (.) and optional thousands separator must be a comma (,)
            'completetype' => 'Complete', 					// Required.  The value Complete indicates that this is the last capture you intend to make.  The value NotComplete indicates that you intend to make additional captures.
            'currencycode' => 'EUR', 					// Three-character currency code
            'invnum' => '', 						// Your invoice number
            'note' => '', 							// Informational note about this setlement that is displayed to the buyer in an email and in his transaction history.  255 character max.
            'softdescriptor' => '', 				// Per transaction description of the payment that is passed to the customer's credit card statement.
            'msgsubid' => '', 						// A message ID used for idempotence to uniquely identify a message.  This ID can later be used to request the latest results for a previous request without generating a new request.  Examples of this include requests due to timeouts or errors during the original request.  38 Char Max
            'storeid' => '', 						// ID of the merchant store.  This field is required for point-of-sale transactions.  Max: 50 char
            'terminalid' => ''						// ID of the terminal.  50 char max.
        );

        $PayPalRequestData = array('DCFields' => $DCFields);

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->DoCapture($PayPalRequestData);

        ob_start();
        print_r($PayPalResult);
        $page = ob_get_contents();
        ob_end_clean();
        $file_name_tmp = "/tmp/paypal_".$transactionId;
        $fw = fopen($file_name_tmp, "w");
        fputs($fw,$page);
        fclose($fw);
        return $PayPalResult;
    }

    public function getTransactionDetails($transactionId) {
        // Create PayPal object.
        $PayPalConfig = array(
            'Sandbox' => $this->sandbox,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'PrintHeaders' => $this->print_headers,
            'LogResults' => $this->log_results,
            'LogPath' => $this->log_path,
        );

        $PayPal = new angelleye\PayPal\PayPal($PayPalConfig);

        $RTFields = array(
            'transactionid' => $transactionId, 							// Required.  PayPal transaction ID for the order you're refunding.
        );

        // You may include up to 16 $MerchantDataVar arrays within the $MerchantDataVars array.
        $MerchantDataVars = array();
        $MerchantDataVar = array(
            'key' => '',                            // The key name of a merchant data key-value pair passed with the transaction.
            'value' => '',                          // The value of the data passed for the key.
        );
        array_push($MerchantDataVars, $MerchantDataVar);

        $PayPalRequestData = array(
            'GTDFields'=>$RTFields,
            'MerchantDataVars' => $MerchantDataVars,
        );

        // Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->GetTransactionDetails($PayPalRequestData);
        return $PayPalResult;
    }
    public function doRefund($transactionId, $amount) {
        // Create PayPal object.
        $PayPalConfig = array(
            'Sandbox' => $this->sandbox,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature,
            'PrintHeaders' => $this->print_headers,
            'LogResults' => $this->log_results,
            'LogPath' => $this->log_path,
        );

        $PayPal = new angelleye\PayPal\PayPal($PayPalConfig);

// Prepare request arrays
        $RTFields = array(
            'transactionid' => $transactionId, 							// Required.  PayPal transaction ID for the order you're refunding.
            'payerid' => '', 								// Encrypted PayPal customer account ID number.  Note:  Either transaction ID or payer ID must be specified.  127 char max
            'invoiceid' => '', 								// Your own invoice tracking number.
            'refundtype' => 'Partial', 							// Required.  Type of refund.  Must be Full, Partial, or Other.
            'amt' => sprintf('%0.2f', $amount), 									// Refund Amt.  Required if refund type is Partial.
            'currencycode' => 'EUR', 							// Three-letter currency code.  Required for Partial Refunds.  Do not use for full refunds.
            'note' => 'rimborso',  									// Custom memo about the refund.  255 char max.
            'retryuntil' => '', 							// Maximum time until you must retry the refund.  Note:  this field does not apply to point-of-sale transactions.
            'refundsource' => '', 							// Type of PayPal funding source (balance or eCheck) that can be used for auto refund.  Values are:  any, default, instant, eCheck
            'merchantstoredetail' => '', 					// Information about the merchant store.
            'refundadvice' => '', 							// Flag to indicate that the buyer was already given store credit for a given transaction.  Values are:  1/0
            'refunditemdetails' => '', 						// Details about the individual items to be returned.
            'msgsubid' => '', 								// A message ID used for idempotence to uniquely identify a message.
            'storeid' => '', 								// ID of a merchant store.  This field is required for point-of-sale transactions.  50 char max.
            'terminalid' => '',								// ID of the terminal.  50 char max.
            'shippingamt' => '',                            // The amount of shipping refunded.
            'taxamt' => '',                                 // The amount of tax refunded.
        );

// You may include up to 16 $MerchantDataVar arrays within the $MerchantDataVars array.
        $MerchantDataVars = array();
        $MerchantDataVar = array(
            'key' => '',                            // The key name of a merchant data key-value pair passed with the transaction.
            'value' => '',                          // The value of the data passed for the key.
        );
        array_push($MerchantDataVars, $MerchantDataVar);

        $PayPalRequestData = array(
            'RTFields'=>$RTFields,
            'MerchantDataVars' => $MerchantDataVars,
        );

// Pass data into class for processing with PayPal and load the response array into $PayPalResult
        $PayPalResult = $PayPal->RefundTransaction($PayPalRequestData);
        return $PayPalResult;
    }

}

//$t = new PayPalProcessor();
//$res =$t->doCapture('6LD46812L0599863B','20.35');
//$res =$t->getTransactionDetails('O-60S39425FJ035235X');
//print_r($res);

