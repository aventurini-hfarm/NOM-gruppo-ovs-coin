<?php

class Amount
{

    /**
     * @var float $Value
     * @access public
     */
    public $Value = null;

    /**
     * @var string $DescrCurrency
     * @access public
     */
    public $DescrCurrency = null;

    /**
     * @param float $Value
     * @param string $DescrCurrency
     * @access public
     */
    public function __construct($Value, $DescrCurrency)
    {
      $this->Value = $Value;
      $this->DescrCurrency = $DescrCurrency;
    }

}
