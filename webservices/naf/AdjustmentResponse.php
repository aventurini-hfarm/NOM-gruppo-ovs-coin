<?php

include_once('Master.php');

class AdjustmentResponse extends Master
{

    /**
     * @var CardInfo $MyCardInfo
     * @access public
     */
    public $MyCardInfo = null;

    /**
     * @param ServiceStatus $MyServiceStatus
     * @param CardInfo $MyCardInfo
     * @access public
     */
    public function __construct($MyServiceStatus, $MyCardInfo)
    {
      parent::__construct($MyServiceStatus);
      $this->MyCardInfo = $MyCardInfo;
    }

}
