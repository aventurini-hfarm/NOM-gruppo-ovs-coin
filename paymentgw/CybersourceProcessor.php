<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 07:37
 */

define( 'MERCHANT_ID', 'nuvo_ovs' );   //RINO 16/07/2016
define( 'TRANSACTION_KEY', 'piIHJbNmgdKUpbKHX7N72oMhbdMaKRlKdl1bDyUa3CtgUqPytFyt2yCO5x4hbLsg3NF+xDl17GTwGwRFNDhB/bTuz37bod82CtzkLdUvRhiYSuW34Mj8LrjQ9sxluq9rCDhvSaZYh1F1+m4vEIR5lErymCvzGR7rW/w78t4FB/nMKVXpCofORSJYV2h7aEXhY7VL5SPZcPe7ue90L/x3uY5Kiy5qnaSHicRkkfML7UiNH4owZ2FKCWx/CImbWf4MuH095M5O2V72ueR4zGFQhnHE2ABcOPVZVdfqZspvECYO8H5yq7UVKT2AN14KlIu6eHqz9Ye38bjCkZdBSX49Nw==' );
define( 'WSDL_URL', 'https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.26.wsdl' );

class CybersourceProcessor extends SoapClient {

    function __construct( $options = null) {
        parent::__construct(WSDL_URL, $options);
    }


    // This section inserts the UsernameToken information in the outgoing SOAP message.
    function __doRequest($request, $location, $action, $version) {

        $user = MERCHANT_ID;
        $password = TRANSACTION_KEY;

        $soapHeader = "<SOAP-ENV:Header xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\"><wsse:Security SOAP-ENV:mustUnderstand=\"1\"><wsse:UsernameToken><wsse:Username>$user</wsse:Username><wsse:Password Type=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText\">$password</wsse:Password></wsse:UsernameToken></wsse:Security></SOAP-ENV:Header>";

        $requestDOM = new DOMDocument('1.0');
        $soapHeaderDOM = new DOMDocument('1.0');

        try {

            $requestDOM->loadXML($request);
            $soapHeaderDOM->loadXML($soapHeader);

            $node = $requestDOM->importNode($soapHeaderDOM->firstChild, true);
            $requestDOM->firstChild->insertBefore(
                $node, $requestDOM->firstChild->firstChild);

            $request = $requestDOM->saveXML();

           // printf( "Modified Request:\n*$request*\n" );

        } catch (DOMException $e) {
            die( 'Error adding UsernameToken: ' . $e->code);
        }

        return parent::__doRequest($request, $location, $action, $version);
    }


    public function doCapture($ref_code , $request_token, $request_id, $total) {

        $request = new stdClass();

        $request->merchantID = MERCHANT_ID;

        // Before using this example, replace the generic value with your own.
        $request->merchantReferenceCode = $ref_code;

        // To help us troubleshoot any problems that you may encounter,
        // please include the following information about your PHP application.
        $request->clientLibrary = "PHP";
        $request->clientLibraryVersion = phpversion();
        $request->clientEnvironment = php_uname();

        // This section contains a sample transaction request for the authorization
        // service with complete billing, payment card, and purchase (two items) information.
        $ccCaptureService = new stdClass();
        $ccCaptureService->run = "true";
        $ccCaptureService->authRequestToken = $request_token;
        $ccCaptureService->authRequestID = $request_id;

        $request->ccCaptureService = $ccCaptureService;
        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = "EUR";
        $purchaseTotals->grandTotalAmount = $total;
        $request->purchaseTotals = $purchaseTotals;



        $reply = $this->runTransaction($request);

        // This section will show all the reply fields.
        //var_dump($reply);

        return $reply;
    }

    public function doRefund($ref_code , $request_token, $request_id, $total) {

        $request = new stdClass();

        $request->merchantID = MERCHANT_ID;

        // Before using this example, replace the generic value with your own.
        $request->merchantReferenceCode = $ref_code;

        // To help us troubleshoot any problems that you may encounter,
        // please include the following information about your PHP application.
        $request->clientLibrary = "PHP";
        $request->clientLibraryVersion = phpversion();
        $request->clientEnvironment = php_uname();


        $ccCreditService = new stdClass();
        $ccCreditService->run = "true";
        //$ccCreditService->authRequestToken = $request_token;
        //$ccCreditService->authRequestID = $request_id;
        $ccCreditService->captureRequestID = $request_id;

        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = "EUR";
        $purchaseTotals->grandTotalAmount = $total;
        $request->purchaseTotals = $purchaseTotals;



        $request->ccCreditService = $ccCreditService;



        $reply = $this->runTransaction($request);



        // This section will show all the reply fields.
        //var_dump($reply);

        return $reply;
    }

    public function authorize($ref_code, $amount) {
        try {


            /*
            To see the functions and types that the SOAP extension can automatically
            generate from the WSDL file, uncomment this section:
            $functions = $soapClient->__getFunctions();
            print_r($functions);
            $types = $soapClient->__getTypes();
            print_r($types);
            */

            $request = new stdClass();

            $request->merchantID = MERCHANT_ID;

            // Before using this example, replace the generic value with your own.
            $request->merchantReferenceCode = $ref_code;

            // To help us troubleshoot any problems that you may encounter,
            // please include the following information about your PHP application.
            $request->clientLibrary = "PHP";
            $request->clientLibraryVersion = phpversion();
            $request->clientEnvironment = php_uname();

            // This section contains a sample transaction request for the authorization
            // service with complete billing, payment card, and purchase (two items) information.
            $ccAuthService = new stdClass();
            $ccAuthService->run = "true";
            $request->ccAuthService = $ccAuthService;

            $billTo = new stdClass();
            $billTo->firstName = "Vincenzo";
            $billTo->lastName = "Sambucaro";
            $billTo->street1 = "Via Solferino 40";
            $billTo->city = "Milano";
            $billTo->state = "IT";
            $billTo->postalCode = "20100";
            $billTo->country = "IT";
            $billTo->email = "vincenzo.sambucaro@nuvo.it";
            $billTo->ipAddress = "10.7.111.111";
            $request->billTo = $billTo;

            $card = new stdClass();
            $card->accountNumber = "4111111111111111";
            $card->expirationMonth = "1";
            $card->expirationYear = "2017";
            $request->card = $card;

            $purchaseTotals = new stdClass();
            $purchaseTotals->currency = "EUR";
            $request->purchaseTotals = $purchaseTotals;

            $item0 = new stdClass();
            $item0->unitPrice = $amount;
            $item0->quantity = "1";
            $item0->id = "0";


            $request->item = array($item0);

            $reply = $this->runTransaction($request);

            // This section will show all the reply fields.
            var_dump($reply);

            // To retrieve individual reply fields, follow these examples.
            printf( "decision = $reply->decision\n" );
            printf( "reasonCode = $reply->reasonCode\n" );
            printf( "requestID = $reply->requestID\n" );
            printf( "requestToken = $reply->requestToken\n" );
            printf( "ccAuthReply->reasonCode = " . $reply->ccAuthReply->reasonCode . "\n");

            return $reply;

        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

}




/*
try {
    $client = new CybersourceProcessor( array());
    $reply  = $client->doCapture('OM_TEST1', 'Ahj77wSR1A//DOaKJUfcBJ/FbgvBGAp/FbgvBH6Q3+koHwyaSZbpAcNnDAnI6gf/hnNFEqPuAAAA6QrZ', '4317114440355000001518',
    '81.46');

    // To retrieve individual reply fields, follow these examples.
    printf( "merchantReferenceCode = $reply->merchantReferenceCode\n" );
    printf( "amount = $reply->amount\n" );
    printf( "reasonCode = $reply->reasonCode\n" );
    printf( "requestID = $reply->requestID\n" );
    printf( "requestToken = $reply->requestToken\n" );


} catch (SoapFault $exception) {
    var_dump(get_class($exception));
    var_dump($exception);
}
*/
