<?php

class RewardListResponse
{

    /**
     * @var RewardResponse $RewardListResult
     * @access public
     */
    public $RewardListResult = null;

    /**
     * @param RewardResponse $RewardListResult
     * @access public
     */
    public function __construct($RewardListResult)
    {
      $this->RewardListResult = $RewardListResult;
    }

}
