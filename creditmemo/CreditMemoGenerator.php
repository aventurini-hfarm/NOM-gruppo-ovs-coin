<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 15/07/15
 * Time: 12:41
 */
ini_set('date.timezone', 'Europe/Rome');

require_once realpath(dirname(__FILE__))."/CreditMemoHelper.php";


$t = new CreditMemoHelper();
//$start = date('Y-m-d 00:00:00');
//$end = date('Y-m-d 23:59:59');
//$start = "2015-07-01 00:00:00";
//$end = "2015-07-31 23:59:59";

$date= date('Y-m-d');
$start_date = date('Y-m-d 00:00:00', strtotime('-1 day', strtotime($date)));
$end_date = date('Y-m-d 23:59:59', strtotime('-1 day', strtotime($date)));

//$start_date = date("2016-10-30 00:00:00");
//$end_date = date("2016-10-30 23:59:59");




$lista = $t->process($start_date, $end_date, true);
//print_r($lista);
$t->doRefund($lista);
//$t->doLoyaltyPointsAdj($lista);

