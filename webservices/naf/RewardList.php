<?php

class RewardList
{

    /**
     * @var RewardRequest $InRewardRequest
     * @access public
     */
    public $InRewardRequest = null;

    /**
     * @param RewardRequest $InRewardRequest
     * @access public
     */
    public function __construct($InRewardRequest)
    {
      $this->InRewardRequest = $InRewardRequest;
    }

}
