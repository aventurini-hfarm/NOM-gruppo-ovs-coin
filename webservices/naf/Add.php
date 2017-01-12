<?php

class Add
{

    /**
     * @var AddRequest $InAddRequest
     * @access public
     */
    public $InAddRequest = null;

    /**
     * @param AddRequest $InAddRequest
     * @access public
     */
    public function __construct($InAddRequest)
    {
      $this->InAddRequest = $InAddRequest;
    }

}
