<?php

class Master
{

    /**
     * @var ServiceStatus $MyServiceStatus
     * @access public
     */
    public $MyServiceStatus = null;

    /**
     * @param ServiceStatus $MyServiceStatus
     * @access public
     */
    public function __construct($MyServiceStatus)
    {
      $this->MyServiceStatus = $MyServiceStatus;
    }

}
