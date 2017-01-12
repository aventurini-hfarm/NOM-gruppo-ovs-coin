<?php

class ServiceStatus
{

    /**
     * @var int $Error
     * @access public
     */
    public $Error = null;

    /**
     * @var string $MessageError
     * @access public
     */
    public $MessageError = null;

    /**
     * @var ApplicationStatCode $StatCode
     * @access public
     */
    public $StatCode = null;

    /**
     * @var string $StatDescr
     * @access public
     */
    public $StatDescr = null;

    /**
     * @param int $Error
     * @param string $MessageError
     * @param string $StatDescr
     * @access public
     */
    public function __construct($Error, $MessageError, $StatDescr)
    {
      $this->Error = $Error;
      $this->MessageError = $MessageError;
      $this->StatDescr = $StatDescr;
    }

}
