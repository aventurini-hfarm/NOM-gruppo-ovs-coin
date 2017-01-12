<?php

class Rollback
{

    /**
     * @var RollbackRequest $InRollbackRequest
     * @access public
     */
    public $InRollbackRequest = null;

    /**
     * @param RollbackRequest $InRollbackRequest
     * @access public
     */
    public function __construct($InRollbackRequest)
    {
      $this->InRollbackRequest = $InRollbackRequest;
    }

}
