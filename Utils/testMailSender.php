<?php
/**
 * Created by PhpStorm.
 * User: Rino
 * Date: 23/06/16
 * Time: 17:10
 */

require_once realpath(dirname(__FILE__)) . "/MailSender.php";


$to = 'cristoforo.mario.antonio.billa.sa@everis.com';
$subject ="test OVS mail";
$message = "messaggio di test";

MailSender::sendEmail($message, $to, $subject);


?>