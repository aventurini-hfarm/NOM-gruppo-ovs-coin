<?php

class NewCustomerAttribute
{

    /**
     * @var string $AttributeName
     * @access public
     */
    public $AttributeName = null;

    /**
     * @var string $AttributeValue
     * @access public
     */
    public $AttributeValue = null;

    /**
     * @param string $AttributeName
     * @param string $AttributeValue
     * @access public
     */
    public function __construct($AttributeName, $AttributeValue)
    {
      $this->AttributeName = $AttributeName;
      $this->AttributeValue = $AttributeValue;
    }

}
