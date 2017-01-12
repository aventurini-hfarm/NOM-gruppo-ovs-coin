<?php

include_once('Master.php');

class RewardResponse extends Master
{

    /**
     * @var RewardInfo[] $MyRewardList
     * @access public
     */
    public $MyRewardList = null;

    /**
     * @param ServiceStatus $MyServiceStatus
     * @param RewardInfo[] $MyRewardList
     * @access public
     */
    public function __construct($MyServiceStatus, $MyRewardList)
    {
      parent::__construct($MyServiceStatus);
      $this->MyRewardList = $MyRewardList;
    }

}
