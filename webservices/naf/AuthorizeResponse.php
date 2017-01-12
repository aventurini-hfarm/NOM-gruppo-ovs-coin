<?php

include_once('Master.php');

class AuthorizeResponse extends Master
{

    /**
     * @var Autorisation $MyAutorisation
     * @access public
     */
    public $MyAutorisation = null;

    /**
     * @var CardInfo $MyCardInfo
     * @access public
     */
    public $MyCardInfo = null;

    /**
     * @param ServiceStatus $MyServiceStatus
     * @param Autorisation $MyAutorisation
     * @param CardInfo $MyCardInfo
     * @access public
     */
    public function __construct($MyServiceStatus, $MyAutorisation, $MyCardInfo)
    {
      parent::__construct($MyServiceStatus);
      $this->MyAutorisation = $MyAutorisation;
      $this->MyCardInfo = $MyCardInfo;
    }

}
