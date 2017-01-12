<?php

class Inquiry
{

    /**
     * @var InquiryRequest $InInquiry
     * @access public
     */
    public $InInquiry = null;

    /**
     * @param InquiryRequest $InInquiry
     * @access public
     */
    public function __construct($InInquiry)
    {
      $this->InInquiry = $InInquiry;
    }

}
