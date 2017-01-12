<?php

class CardInfo
{

    /**
     * @var string $TokenStatus
     * @access public
     */
    public $TokenStatus = null;

    /**
     * @var string $NewToken
     * @access public
     */
    public $NewToken = null;

    /**
     * @var string $CustName
     * @access public
     */
    public $CustName = null;

    /**
     * @var string $CustSurname
     * @access public
     */
    public $CustSurname = null;

    /**
     * @var string $CardType
     * @access public
     */
    public $CardType = null;

    /**
     * @var string $TokenType
     * @access public
     */
    public $TokenType = null;

    /**
     * @var string $CustomerType
     * @access public
     */
    public $CustomerType = null;

    /**
     * @var NAFWallet[] $Wallet
     * @access public
     */
    public $Wallet = null;

    /**
     * @var int $PointsBalance
     * @access public
     */
    public $PointsBalance = null;

    /**
     * @var int $MissingPoints
     * @access public
     */
    public $MissingPoints = null;

    /**
     * @var string $FirstReward
     * @access public
     */
    public $FirstReward = null;

    /**
     * @param string $TokenStatus
     * @param string $NewToken
     * @param string $CustName
     * @param string $CustSurname
     * @param string $CardType
     * @param string $TokenType
     * @param string $CustomerType
     * @param NAFWallet[] $Wallet
     * @param int $PointsBalance
     * @param int $MissingPoints
     * @param string $FirstReward
     * @access public
     */
    public function __construct($TokenStatus, $NewToken, $CustName, $CustSurname, $CardType, $TokenType, $CustomerType, $Wallet, $PointsBalance, $MissingPoints, $FirstReward)
    {
      $this->TokenStatus = $TokenStatus;
      $this->NewToken = $NewToken;
      $this->CustName = $CustName;
      $this->CustSurname = $CustSurname;
      $this->CardType = $CardType;
      $this->TokenType = $TokenType;
      $this->CustomerType = $CustomerType;
      $this->Wallet = $Wallet;
      $this->PointsBalance = $PointsBalance;
      $this->MissingPoints = $MissingPoints;
      $this->FirstReward = $FirstReward;
    }

}
