<?php

class NewToken
{

    /**
     * @var int $source
     * @access public
     */
    public $source = null;

    /**
     * @var string $userId
     * @access public
     */
    public $userId = null;

    /**
     * @var NewCustomerAttribute[] $ListCustomerAttribute
     * @access public
     */
    public $ListCustomerAttribute = null;

    /**
     * @param int $source
     * @param string $userId
     * @param NewCustomerAttribute[] $ListCustomerAttribute
     * @access public
     */
    public function __construct($source, $userId, $ListCustomerAttribute)
    {
      $this->source = $source;
      $this->userId = $userId;
      $this->ListCustomerAttribute = $ListCustomerAttribute;
    }

}
