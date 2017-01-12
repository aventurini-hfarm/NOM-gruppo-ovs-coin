<?php

include_once('Master.php');

class NewTokenResponse extends Master
{

    /**
     * @var string $MyTokenNumber
     * @access public
     */
    public $MyTokenNumber = null;

    /**
     * @param ServiceStatus $MyServiceStatus
     * @param string $MyTokenNumber
     * @access public
     */
    public function __construct($MyServiceStatus, $MyTokenNumber)
    {
      parent::__construct($MyServiceStatus);
      $this->MyTokenNumber = $MyTokenNumber;
    }

}
