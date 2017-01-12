<?php

class InquiryRequest
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
     * @param int $Source_ID
     * @param string $TokenNumber
     * @access public
     */
    public function __construct($Source_ID, $TokenNumber)
    {
      $this->Source_ID = $Source_ID;
      $this->TokenNumber = $TokenNumber;
    }

}
