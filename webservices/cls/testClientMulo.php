<?php
/**
 * Created by PhpStorm.
 * User: rino
 * Date: 10/07/15
 * Time: 13:32
 */
//wget http://localhost/orderHistory/services/orderHistory/GetOrderByWebUserId?site=CC&UserId=00019601
//wget http://localhost/orderHistory/services/orderHistory/?wsdl
/*$client = new SoapClient('http://192.168.165.243:8080/services/orderHistory?wsdl', array('trace' => 1));

print_r($client->__getFunctions());

$obj = new stdClass();
$obj->webuserid = "00008070";
$obj->site = "5463";
$info = $client->__soapCall("GetOrderByWebUserId", array($obj));
print_r($info);

echo "\nDettaglio ordine";
$obj->ordernumber = "00078849";
$info = $client->__soapCall("GetOrderLinesByWebUserId", array($obj));
print_r($info);*/

//echo ini_set('soap.wsdl_cache_enabled',0);
//echo "\n";
//echo ini_set('soap.wsdl_cache_ttl',0);
//echo "\n";

require_once "/home/OrderManagement/webservices/cls/cls.php";


//print_r(Cls::operationPoints("104966221","REMOVE","1"));
print_r(Cls::getPoints("145311429"));


class LocalSoapClient extends SoapClient {

    function __doRequest($request, $location, $action, $version) {

        //$request = preg_replace('/<ns1:(\w+)/', '<$1 xmlns="'.$namespace.'"', $request, 1);
        //$request = preg_replace('/<ns1:(\w+)/', '<$1', $request);
        //$request = str_replace(array('/ns1:', 'xmlns:ns1="'.$namespace.'"'), array('/', ''), $request);

        //$request = preg_replace('/<ns1:getPoints\/>/', '<ns1:getPoints><ns1:getPointsRequest><cardCode>000000002782</cardCode></cls:getPointsRequest></ns1:getPoints>',$request,1);

        //$request2="";
        // parent call
        return parent::__doRequest($request, $location, $action, $version);
    }
}



/*try {
    //$client = new LocalSoapClient('http://213.215.155.154/ovs/services/cls?wsdl',array(
    $client = new LocalSoapClient('cls.wsdl',array(
        'connection_timeout' => 15,
        'soap_version' => SOAP_1_1,
        'trace' => 1,
        'exceptions' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE));
    print_r($client->__getFunctions());
    //print_r($client->__getTypes());

    $getPointsRequest = new stdClass();
    $getPointsRequest->cardCode="000000002782";

    $getPoints = new stdClass();
    $getPoints->getPointsRequest=$getPointsRequest;

    $getPointsResponse = $client->getPoints($getPoints);

    print_r($getPointsResponse);

}
catch (SoapFault $exception) {
    echo $exception->getMessage();
}*/


/*try {
    //$client = new LocalSoapClient('http://213.215.155.154/ovs/services/cls?wsdl',array(
    $client = new LocalSoapClient('cls.wsdl',array(
        'connection_timeout' => 15,
        'soap_version' => SOAP_1_1,
        'trace' => 1,
        'exceptions' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE));
    //print_r($client->__getFunctions());
    //print_r($client->__getTypes());


    $OperationPointsRequest = new stdClass();
    $OperationPointsRequest->cardCode="000000002782";
    $OperationPointsRequest->operation="ADD";
    $OperationPointsRequest->storeId="";
    $OperationPointsRequest->transactionId ="";
    $OperationPointsRequest->points="10";
    $OperationPointsRequest->retryOnError="";


    $operationPoints = new stdClass();
    $operationPoints->operationPointsRequest=$OperationPointsRequest;

    $operationPointsResponse = $client->operationPoints($operationPoints);

    print_r($operationPointsResponse);

}
catch (SoapFault $exception) {
    echo $exception->getMessage();
}*/


/*try {
    $client = new SoapClient('cls.wsdl');
    $OperationPointsRequest = new stdClass();
    $OperationPointsRequest->cardCode="000000002782";
    $OperationPointsRequest->operation="REMOVE";
    $OperationPointsRequest->transactionId="StornoNomOvs";
    $OperationPointsRequest->retryOnError="";
    $OperationPointsRequest->points="5";
    $operationPoints = new stdClass();
    $operationPoints->operationPointsRequest=$OperationPointsRequest;
    $operationPointsResponse = $client->operationPoints($operationPoints);
    print_r($operationPointsResponse);
}
catch (SoapFault $exception) {
    echo $exception->getMessage();
}*/





// DA NOM OVS TEST
// curl -H "Content-Type: text/xml; charset=utf-8" -H "SOAPAction:"  -d @getPointsRequest.xml -X POST http://213.215.155.156/ovs/services/cls
// DA NOM OVS PRODUZIONE
// curl -H "Content-Type: text/xml; charset=utf-8" -H "SOAPAction:"  -d @getPointsRequest.xml -X POST http://213.215.155.154/ovs/services/cls