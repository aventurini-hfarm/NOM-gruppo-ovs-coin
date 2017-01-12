<?php


class Cls {



    public function getPoints($cardCode) {

        try {

            $client = new LocalSoapClient('cls.wsdl',array(
                'connection_timeout' => 15,
                'soap_version' => SOAP_1_1,
                'trace' => 1,
                'exceptions' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE));

            $getPointsRequest = new stdClass();
            $getPointsRequest->cardCode=$cardCode;

            $getPoints = new stdClass();
            $getPoints->getPointsRequest=$getPointsRequest;

            $getPointsResponse = $client->getPoints($getPoints);

            return $getPointsResponse;
        }
        catch (SoapFault $exception) {
            echo $exception->getMessage();
        }

    }


    public function operationPoints($cardCode, $operation, $points)
    {
        try {
            $client = new SoapClient('cls.wsdl',array(
                'connection_timeout' => 15,
                'soap_version' => SOAP_1_1,
                'trace' => 1,
                'exceptions' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE));
            $OperationPointsRequest = new stdClass();
            $OperationPointsRequest->cardCode = $cardCode;
            $OperationPointsRequest->operation = $operation;
            $OperationPointsRequest->transactionId = "StornoNomOvs";
            $OperationPointsRequest->retryOnError = "";
            $OperationPointsRequest->points = $points;
            $operationPoints = new stdClass();
            $operationPoints->operationPointsRequest = $OperationPointsRequest;
            $operationPointsResponse = $client->operationPoints($operationPoints);
            return $operationPointsResponse;
        } catch (SoapFault $exception) {
            echo $exception->getMessage();
        }
    }


}
