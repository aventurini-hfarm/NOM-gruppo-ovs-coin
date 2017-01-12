<?php

class RewardInfo
{

    /**
     * @var string $Name
     * @access public
     */
    public $Name = null;

    /**
     * @var string $Description
     * @access public
     */
    public $Description = null;

    /**
     * @var string $Code
     * @access public
     */
    public $Code = null;

    /**
     * @var int $PointsValue
     * @access public
     */
    public $PointsValue = null;

    /**
     * @var dateTime $EndDate
     * @access public
     */
    public $EndDate = null;

    /**
     * @param string $Name
     * @param string $Description
     * @param string $Code
     * @param int $PointsValue
     * @param dateTime $EndDate
     * @access public
     */
    public function __construct($Name, $Description, $Code, $PointsValue, $EndDate)
    {
      $this->Name = $Name;
      $this->Description = $Description;
      $this->Code = $Code;
      $this->PointsValue = $PointsValue;
      $this->EndDate = $EndDate;
    }

}
