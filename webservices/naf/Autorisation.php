<?php

class Autorisation
{

    /**
     * @var NAFWallet $CurrentWallet
     * @access public
     */
    public $CurrentWallet = null;

    /**
     * @var NAFWallet $PreviousWallet
     * @access public
     */
    public $PreviousWallet = null;

    /**
     * @var int $Token
     * @access public
     */
    public $Token = null;

    /**
     * @param NAFWallet $CurrentWallet
     * @param NAFWallet $PreviousWallet
     * @access public
     */
    public function __construct($CurrentWallet, $PreviousWallet)
    {
      $this->CurrentWallet = $CurrentWallet;
      $this->PreviousWallet = $PreviousWallet;
    }

}
