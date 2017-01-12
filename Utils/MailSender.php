<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 05/08/15
 * Time: 23:08
 */
require_once realpath(dirname(__FILE__)) . "/mailer/PHPMailerAutoload.php";

class MailSender {

    public static function sendEmail($message, $email_address, $subject) {

        $mail = new PHPMailer;
        $mail->Mailer   = 'sendmail';       // Rino 20/06/2016
        $mail->Sender   = 'noreply@ovs.it'; // Rino 20/06/2016
        $mail->From 	= 'noreply@ovs.it';
        $mail->FromName = 'OVS Online Store';
        $mail->Subject 	= $subject;
        $mail->Body		= $message;

        $mail->addAddress($email_address);

        $mail->isHTML(false);
        $mail->send();
    }

}