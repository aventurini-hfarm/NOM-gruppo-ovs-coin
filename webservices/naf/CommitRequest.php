<?php

class CommitRequest
{

    /**
     * @var int $Source_ID
     * @access public
     */
    public $Source_ID = null;

    /**
     * @var int $Token
     * @access public
     */
    public $Token = null;

    /**
     * @param int $Source_ID
     * @param int $Token
     * @access public
     */
    public function __construct($Source_ID, $Token)
    {
      $this->Source_ID = $Source_ID;
      $this->Token = $Token;
    }

}
