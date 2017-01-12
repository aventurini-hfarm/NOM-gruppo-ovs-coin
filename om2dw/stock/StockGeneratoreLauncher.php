<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/06/15
 * Time: 16:48
 */

require_once realpath(dirname(__FILE__))."/StockGenerator.php";

$t = new StockGenerator();
$t->run();
