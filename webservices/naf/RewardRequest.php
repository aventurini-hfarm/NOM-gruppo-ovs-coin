<?php

class RewardRequest
{

    /**
     * @var int $Source_ID
     * @access public
     */
    public $Source_ID = null;

    /**
     * @param int $Source_ID
     * @access public
     */
    public function __construct($Source_ID)
    {
      $this->Source_ID = $Source_ID;
    }

}
