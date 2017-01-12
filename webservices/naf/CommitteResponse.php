<?php

include_once('Master.php');

class CommitteResponse extends Master
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
