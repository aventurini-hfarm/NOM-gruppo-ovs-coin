<?php

class AddRequest
{

    /**
     * @var int $Source_ID
     * @access public
     */
    public $Source_ID = null;

    /**
     * @var string $TokenNumber
     * @access public
     */
    public $TokenNumber = null;

    /**
     * @var int $OrderNumber
     * @access public
     */
    public $OrderNumber = null;

    /**
     * @var int $AddPoints
     * @access public
     */
    public $AddPoints = null;

    /**
     * @var float $AddNAFTransAmt
     * @access public
     */
    public $AddNAFTransAmt = null;

    /**
     * @param int $Source_ID
     * @param string $TokenNumber
     * @param int $OrderNumber
     * @param int $AddPoints
     * @param float $AddNAFTransAmt
     * @access public
     */
    public function __construct($Source_ID, $TokenNumber, $OrderNumber, $AddPoints, $AddNAFTransAmt)
    {
      $this->Source_ID = $Source_ID;
      $this->TokenNumber = $TokenNumber;
      $this->OrderNumber = $OrderNumber;
      $this->AddPoints = $AddPoints;
      $this->AddNAFTransAmt = $AddNAFTransAmt;
    }

}
