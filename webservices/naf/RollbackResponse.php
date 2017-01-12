<?php

include_once('Master.php');

class RollbackResponse extends Master
{

    /**
     * @param ServiceStatus $MyServiceStatus
     * @access public
     */
    public function __construct($MyServiceStatus)
    {
      parent::__construct($MyServiceStatus);
    }

}
