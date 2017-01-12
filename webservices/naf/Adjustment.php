<?php

class Adjustment
{

    /**
     * @var AdjustmentRequest $InAdjustmentRequest
     * @access public
     */
    public $InAdjustmentRequest = null;

    /**
     * @param AdjustmentRequest $InAdjustmentRequest
     * @access public
     */
    public function __construct($InAdjustmentRequest)
    {
      $this->InAdjustmentRequest = $InAdjustmentRequest;
    }

}
