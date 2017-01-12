<?php

class NAFWallet
{

    /**
     * @var Amount $WltAmount
     * @access public
     */
    public $WltAmount = null;

    /**
     * @var string $CollectionCode
     * @access public
     */
    public $CollectionCode = null;

    /**
     * @var string $DiscountCode
     * @access public
     */
    public $DiscountCode = null;

    /**
     * @var Amount $DiscountValue
     * @access public
     */
    public $DiscountValue = null;

    /**
     * @var Amount $Target
     * @access public
     */
    public $Target = null;

    /**
     * @var Amount $PrevYearSpend
     * @access public
     */
    public $PrevYearSpend = null;

    /**
     * @var Amount $CurrYearSpend
     * @access public
     */
    public $CurrYearSpend = null;

    /**
     * @var Amount $DeltaTarget
     * @access public
     */
    public $DeltaTarget = null;

    /**
     * @param Amount $WltAmount
     * @param string $CollectionCode
     * @param string $DiscountCode
     * @param Amount $DiscountValue
     * @param Amount $Target
     * @param Amount $PrevYearSpend
     * @param Amount $CurrYearSpend
     * @param Amount $DeltaTarget
     * @access public
     */
    public function __construct($WltAmount, $CollectionCode, $DiscountCode, $DiscountValue, $Target, $PrevYearSpend, $CurrYearSpend, $DeltaTarget)
    {
      $this->WltAmount = $WltAmount;
      $this->CollectionCode = $CollectionCode;
      $this->DiscountCode = $DiscountCode;
      $this->DiscountValue = $DiscountValue;
      $this->Target = $Target;
      $this->PrevYearSpend = $PrevYearSpend;
      $this->CurrYearSpend = $CurrYearSpend;
      $this->DeltaTarget = $DeltaTarget;
    }

}
