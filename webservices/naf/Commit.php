<?php

class Commit
{

    /**
     * @var CommitRequest $InCommitRequest
     * @access public
     */
    public $InCommitRequest = null;

    /**
     * @param CommitRequest $InCommitRequest
     * @access public
     */
    public function __construct($InCommitRequest)
    {
      $this->InCommitRequest = $InCommitRequest;
    }

}
