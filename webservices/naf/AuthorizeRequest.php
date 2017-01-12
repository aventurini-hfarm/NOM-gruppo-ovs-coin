<?php

class AuthorizeRequest
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
     * @var int $RedPoints
     * @access public
     */
    public $RedPoints = null;

    /**
     * @var float $RedNAF
     * @access public
     */
    public $RedNAF = null;

    /**
     * @param int $Source_ID
     * @param string $TokenNumber
     * @param int $OrderNumber
     * @param int $RedPoints
     * @param float $RedNAF
     * @access public
     */
    public function __construct($Source_ID, $TokenNumber, $OrderNumber, $RedPoints, $RedNAF)
    {
      $this->Source_ID = $Source_ID;
      $this->TokenNumber = $TokenNumber;
      $this->OrderNumber = $OrderNumber;
      $this->RedPoints = $RedPoints;
      $this->RedNAF = $RedNAF;
    }

}
