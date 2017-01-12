<?php

class CommitResponse
{

    /**
     * @var CommitteResponse $CommitResult
     * @access public
     */
    public $CommitResult = null;

    /**
     * @param CommitteResponse $CommitResult
     * @access public
     */
    public function __construct($CommitResult)
    {
      $this->CommitResult = $CommitResult;
    }

}
