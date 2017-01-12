<?php

include_once('AddRequest.php');

class AdjustmentRequest extends AddRequest
{

    /**
     * @var float $NAFDiscount
     * @access public
     */
    public $NAFDiscount = null;

    /**
     * @var string $TimeAdj
     * @access public
     */
    public $TimeAdj = null;

    /**
     * @param int $Source_ID
     * @param string $TokenNumber
     * @param int $OrderNumber
     * @param int $AddPoints
     * @param float $AddNAFTransAmt
     * @param float $NAFDiscount
     * @param string $TimeAdj
     * @access public
     */
    public function __construct($Source_ID, $TokenNumber, $OrderNumber, $AddPoints, $AddNAFTransAmt, $NAFDiscount, $TimeAdj)
    {
      parent::__construct($Source_ID, $TokenNumber, $OrderNumber, $AddPoints, $AddNAFTransAmt);
      $this->NAFDiscount = $NAFDiscount;
      $this->TimeAdj = $TimeAdj;
    }

}
