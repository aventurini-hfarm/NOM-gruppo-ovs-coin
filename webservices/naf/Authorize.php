<?php

class Authorize
{

    /**
     * @var AuthorizeRequest $InAuthorizeRequest
     * @access public
     */
    public $InAuthorizeRequest = null;

    /**
     * @param AuthorizeRequest $InAuthorizeRequest
     * @access public
     */
    public function __construct($InAuthorizeRequest)
    {
      $this->InAuthorizeRequest = $InAuthorizeRequest;
    }

}
