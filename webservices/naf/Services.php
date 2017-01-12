<?php

include_once('Inquiry.php');
include_once('InquiryRequest.php');
include_once('InquiryResponse.php');
include_once('Master.php');
include_once('ServiceStatus.php');
include_once('ApplicationStatCode.php');
include_once('CardInfo.php');
include_once('NAFWallet.php');
include_once('Amount.php');
include_once('RewardList.php');
include_once('RewardRequest.php');
include_once('RewardListResponse.php');
include_once('RewardResponse.php');
include_once('RewardInfo.php');
include_once('Authorize.php');
include_once('AuthorizeRequest.php');
include_once('AuthorizeResponse.php');
include_once('Autorisation.php');
include_once('Commit.php');
include_once('CommitRequest.php');
include_once('CommitResponse.php');
include_once('CommitteResponse.php');
include_once('Rollback.php');
include_once('RollbackRequest.php');
include_once('RollbackResponse.php');
include_once('Add.php');
include_once('AddRequest.php');
include_once('AddResponse.php');
include_once('Adjustment.php');
include_once('AdjustmentRequest.php');
include_once('AdjustmentResponse.php');
include_once('NewToken.php');
include_once('NewCustomerAttribute.php');
include_once('NewTokenResponse.php');

class Services extends \SoapClient
{

    /**
     * @var array $classmap The defined classes
     * @access private
     */
    private static $classmap = array(
      'Inquiry' => '\Inquiry',
      'InquiryRequest' => '\InquiryRequest',
      'InquiryResponse' => '\InquiryResponse',
      'Master' => '\Master',
      'ServiceStatus' => '\ServiceStatus',
      'CardInfo' => '\CardInfo',
      'NAFWallet' => '\NAFWallet',
      'Amount' => '\Amount',
      'RewardList' => '\RewardList',
      'RewardRequest' => '\RewardRequest',
      'RewardListResponse' => '\RewardListResponse',
      'RewardResponse' => '\RewardResponse',
      'RewardInfo' => '\RewardInfo',
      'Authorize' => '\Authorize',
      'AuthorizeRequest' => '\AuthorizeRequest',
      'AuthorizeResponse' => '\AuthorizeResponse',
      'Autorisation' => '\Autorisation',
      'Commit' => '\Commit',
      'CommitRequest' => '\CommitRequest',
      'CommitResponse' => '\CommitResponse',
      'CommitteResponse' => '\CommitteResponse',
      'Rollback' => '\Rollback',
      'RollbackRequest' => '\RollbackRequest',
      'RollbackResponse' => '\RollbackResponse',
      'Add' => '\Add',
      'AddRequest' => '\AddRequest',
      'AddResponse' => '\AddResponse',
      'Adjustment' => '\Adjustment',
      'AdjustmentRequest' => '\AdjustmentRequest',
      'AdjustmentResponse' => '\AdjustmentResponse',
      'NewToken' => '\NewToken',
      'NewCustomerAttribute' => '\NewCustomerAttribute',
      'NewTokenResponse' => '\NewTokenResponse');

    /**
     * @param array $options A array of config values
     * @param string $wsdl The wsdl file to use
     * @access public
     */

	public function __construct(array $options = array(), $wsdl = 'https://wsgsapnafprod.gruppocoin.it/WsNaf/Services.asmx?wsdl')
    {
      foreach (self::$classmap as $key => $value) {
        if (!isset($options['classmap'][$key])) {
          $options['classmap'][$key] = $value;
        }
      }
      
      parent::__construct($wsdl, $options);
    }

    /**
     * Interrogazione tessera
     *
     * @param Inquiry $parameters
     * @access public
     * @return InquiryResponse
     */
    public function Inquiry(Inquiry $parameters)
    {
      return $this->__soapCall('Inquiry', array($parameters));
    }

    /**
     * Lista premi riscattabili con punti
     *
     * @param RewardList $parameters
     * @access public
     * @return RewardListResponse
     */
    public function RewardList(RewardList $parameters)
    {
      return $this->__soapCall('RewardList', array($parameters));
    }

    /**
     * Autorizzzazione punti e borsellino elettronico
     *
     * @param Authorize $parameters
     * @access public
     * @return AuthorizeResponse
     */
    public function Authorize(Authorize $parameters)
    {
      return $this->__soapCall('Authorize', array($parameters));
    }

    /**
     * Conferma utilizzo punti e/o borsellino elettronico
     *
     * @param Commit $parameters
     * @access public
     * @return CommitResponse
     */
    public function Commit(Commit $parameters)
    {
      return $this->__soapCall('Commit', array($parameters));
    }

    /**
     * Annullla utilizzo punti e/o borsellino elettronico
     *
     * @param Rollback $parameters
     * @access public
     * @return RollbackResponse
     */
    public function Rollback(Rollback $parameters)
    {
      return $this->__soapCall('Rollback', array($parameters));
    }

    /**
     * Incrementa saldo punti e spesa utile per il calcolo del NAF
     *
     * @param Add $parameters
     * @access public
     * @return AddResponse
     */
    public function Add(Add $parameters)
    {
      return $this->__soapCall('Add', array($parameters));
    }

    /**
     * Aggiustamenti saldo punti, spesa utile per il calcolo del NAF, sconto NAF
     *
     * @param Adjustment $parameters
     * @access public
     * @return AdjustmentResponse
     */
    public function Adjustment(Adjustment $parameters)
    {
      return $this->__soapCall('Adjustment', array($parameters));
    }

    /**
     * Associa un cliente ad una nuova tessera
     *
     * @param NewToken $parameters
     * @access public
     * @return NewTokenResponse
     */
    public function NewToken(NewToken $parameters)
    {
      return $this->__soapCall('NewToken', array($parameters));
    }

}
