<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 29/07/15
 * Time: 18:22
 */

require_once realpath(dirname(__FILE__)) . "/../../Utils/mailer/PHPMailerAutoload.php";

class TestMail {

    public function inviaEmailConfermaOrdine() {

        //return;


        $message = "Prova";

        $email_address = "vincenzo.sambucaro@nuvo.it";

        $mail = new PHPMailer;
        $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
        $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
        $mail->From 	= 'noreply@ovs.it';
        $mail->FromName = 'OVS Online Store';
        $mail->Subject 	= 'Conferma Spedizione OVS #';
        $mail->Body		= $message;

        $mail->addAddress($email_address);
        //$mail->addBCC('');

        $mail->isHTML(true);
        $mail->send();


    }

}

$t = new TestMail();
$t->inviaEmailConfermaOrdine();
