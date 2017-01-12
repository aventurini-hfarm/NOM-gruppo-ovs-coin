<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 08:56
 */
require_once realpath(dirname(__FILE__)) . "/../omdb/PaymentDBHelper.php";
require_once realpath(dirname(__FILE__))."/../dw2om/orders/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__))."/../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/PayPalProcessor.php";
require_once realpath(dirname(__FILE__))."/CybersourceProcessor.php";
require_once realpath(dirname(__FILE__)) . "/../Utils/MailSender.php";

class PaymentProcessor {

    private $order_number;
    private $log;

    function __construct($order_no) {
        $this->order_number = $order_no;
        $this->log = new KLogger('/var/log/nom/payment_processor.log',KLogger::DEBUG);
    }

    public function getTransactionDetails() {

        $manager = new PaymentDBHelper($this->order_number);
        $paymentObj = $manager->getPaymentInfo();

        $auth_id=$paymentObj->auth_id;

        $pgw = new PayPalProcessor();
        try {


            $reply = $pgw->getTransactionDetails($auth_id);
            $errorCode = $reply['L_ERRORCODE0'];
            if ($errorCode) {
                $reasonCode = $errorCode;
                $decision = $reply['L_LONGMESSAGE0'];
            } else {
                $reasonCode = "None";
                $decision = "None";
            }

            print_r($reply);


        } catch (SoapFault $exception) {
            print_r($exception);

        }


    }

    public function doRefund($amount) {
        $this->log->LogInfo("Do Refund: ".$this->order_number);
        $manager = new PaymentDBHelper($this->order_number);
        $paymentObj = $manager->getPaymentInfo();
        $this->log->LogInfo("Refund Payment: ".$this->order_number);
        $paymentObj->amount = round ($amount,2);

        $this->log->LogInfo("Refund Amount: ".$amount);

        if ($paymentObj->payment_method=='CYBERSOURCE' && $paymentObj->refund_reason_code == '') {   //RINO 21/09/2016   se refund_reason_code è vuota la nota di credito non è stata pagata
            $pgw = new CybersourceProcessor(array());
            try {

                //$reply = $pgw->doCapture($paymentObj->ref_code, $paymentObj->auth_token, $paymentObj->auth_id, $paymentObj->amount);
                $reply = $pgw->doRefund($paymentObj->ref_code, $paymentObj->auth_token, $paymentObj->auth_id, $paymentObj->amount);
                $reasonCode = $reply->reasonCode;
                $decision = $reply->decision;
                $this->log->LogInfo("Refund Reason Code: ".$reasonCode);
                $this->log->LogDebug("Refund Decision Code: ".$decision);
                $manager->updateDoRefundResponse($reasonCode, $decision);


            } catch (SoapFault $exception) {
                $this->log->LogError ("Errore refund: ".$exception);
                //var_dump(get_class($exception));
                //var_dump($exception);
                $manager->updateDoRefundResponse('9999', $exception->getMessage());

            }

        }
        if ($paymentObj->payment_method=='PAYPAL' && $paymentObj->refund_reason_code == '') { //RINO 21/09/2016   se refund_reason_code è vuota la nota di credito non è stata pagata

            $pgw = new PayPalProcessor();
            try {

                //$reply = $pgw->doCapture($paymentObj->auth_id, $paymentObj->amount);
               // $reply = $pgw->doRefund($paymentObj->auth_id, $paymentObj->amount);
                $reply = $pgw->doRefund($paymentObj->refund_trxid, $paymentObj->amount);
                $errorCode = $reply['L_ERRORCODE0'];
                if ($errorCode) {
                    $reasonCode = $errorCode;
                    $decision = $reply['L_LONGMESSAGE0'];
                } else {
                    $reasonCode = "None";
                    $decision = "None";
                }

                $this->log->LogInfo ("Refund Reason Code: ".$reasonCode);
                $this->log->LogInfo("Refund Decision Code: ".$decision);

                $manager->updateDoRefundResponse($reasonCode, $decision);


            } catch (SoapFault $exception) {
                $this->log->LogError("Errore pagamento: ".$exception);
                //var_dump(get_class($exception));
                //var_dump($exception);
                $manager->updateDoRefundResponse('9999', $exception->getMessage());

            }
        }

        $this->log->LogInfo("Refund Payment: ".$this->order_number." OK");
    }


    public function executePayment() {
        $magOrderHelper = new MagentoOrderHelper();
        $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);
        //$magOrderHelper->setStatusComplete($increment_id);

        //devo prendere il valore dall'ordine perchè in caso di reso è diverso il valore dal paymentinf
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $orderValue = $order->getBaseGrandTotal();

        $payment = $order->getPayment();
        $payment_method_selected = $payment->getMethod();
        //print_r($payment->getData());
        //echo "\nPayment Method: ".$payment_method_selected;

        if ($payment_method_selected=='free') {  //RINO 27/07/2016 $payment_method_selected==free equivale a CHIOSCO
            //Chiosco
            $this->log->LogInfo("Payment: ".$this->order_number." OK CHIOSCO");
            return true;
        }

        if ($payment_method_selected=='cashondelivery') {
            //Contrassegno
            $this->log->LogInfo("Payment: ".$this->order_number." OK CONTRASSEGNO");
            return true;
        }

        $manager = new PaymentDBHelper($this->order_number);
        $paymentObj = $manager->getPaymentInfo();
        //echo "\nProcessing payment: ".$this->order_number;
        //print_r($paymentObj);
        if (!$paymentObj) {
            //echo "\nErrore nessun payment object";
            $this->log->LogError("Errore nessun payment info per ordine:".$this->order_number);
            return false;
        }
        $this->log->LogInfo("Payment: ".$this->order_number);
        //print_r($paymentObj);



        //tutto il pezzo fino alla return è solo per il test
        //echo "\nSetting Mangento Status TEST";

        $paymentObj->amount = round ($orderValue,2);
        $this->log->LogInfo("Payment Amount: $orderValue (".$this->order_number.")");

        //$reasonCode = "DUMMY100";
        //$decision = "FAKE100";
        //$manager->updateDoCaptureResponse($reasonCode, $decision);
        //return true;


        if ($paymentObj->payment_method=='CYBERSOURCE') {
            $pgw = new CybersourceProcessor(array());
            try {
                //TODO GESTIONE AMOUNT PARZIALI andando a prendere il valore dell'ordine che potrebbe essere stato modificato dal magazziniere
                $reply = $pgw->doCapture($paymentObj->ref_code, $paymentObj->auth_token, $paymentObj->auth_id, $paymentObj->amount);
                $reasonCode = $reply->reasonCode;
                $decision = $reply->decision;
                $this->log->LogDebug("Reason Code: ".$reasonCode);
                $this->log->LogDebug("Decision Code: ".$decision);
                $manager->updateDoCaptureResponse($reasonCode, $decision);

                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);

                //TODO SISTEMARE GESTIONE ERRORI PAGAMENTI
                //$magOrderHelper->setStatusComplete($increment_id);  //TODO  08/07/2017 lo status complete vinen impostatato al doInvoice

                $comment = "Capture  Result: [$reasonCode,$decision]";
                $closed = $reasonCode==100 ? 1: 0;
                //$magOrderHelper->setTransaction($increment_id, $paymentObj->auth_id, $paymentObj->auth_id, "authorize_capture", $comment, $closed);

                return true;

            } catch (SoapFault $exception) {
                $this->log->LogError ("Errore pagamento: ".$exception);
                //var_dump(get_class($exception));
                //var_dump($exception);
                $manager->updateDoCaptureResponse('9999', $exception->getMessage());
                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);
                $magOrderHelper->setStatusPendingPayment($increment_id);
                $comment = "Capture fallita [999,".$exception->getMessage()."]";
                $closed = 0;
                //$magOrderHelper->setTransaction($increment_id, $paymentObj->auth_id, $paymentObj->auth_id, "authorize_capture", $comment, $closed);
                MailSender::sendEmail("Attenzione errore pagamento: ".$this->order_number,'nomovs@gmail.com','Warning NOM');
                return false;
            }

        } else {

            $pgw = new PayPalProcessor();
            try {
                //TODO GESTIONE AMOUNT PARZIALI andando a prendere il valore dell'ordine che potrebbe essere stato modificato dal magazziniere
                $reply = $pgw->doCapture($paymentObj->auth_id, $paymentObj->amount);
                $refund_trxId = $reply['TRANSACTIONID'];

                $errorCode = $reply['L_ERRORCODE'];
                if ($errorCode) {
                    $reasonCode = $errorCode;
                    $decision = $reply['L_SHORTMESSAGE'];
                } else {
                    $reasonCode = "None";
                    $decision = "None";
                }

                $this->log->LogDebug ("Error Code: ".$errorCode);
                $this->log->LogDebug ("Reason Code: ".$reasonCode);
                $this->log->LogDebug("Decision Code: ".$decision);

                $manager->updateDoCaptureResponse($reasonCode, $decision, $refund_trxId);

                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);
                /*if ($errorCode) {  // TODO Rino 08/07/2016   Ho gestito diversamente gli errori e lo gli status
                    $this->log->LogDebug ("Set Pending");
                    $magOrderHelper->setStatusPendingPayment($increment_id); //TODO verificare se effettivamente deve rimanere così come stato
                }
                else {
                    $this->log->LogDebug ("Set Complete");
                    $magOrderHelper->setStatusComplete($increment_id);
                }*/

                //Vincenzo 06122016
                $errors = $reply['ERRORS'];
                if (!empty($errors)) {
                    $email_address = "vincenzo.sambucaro@h-farm.com";

                    $mail = new PHPMailer;
                    $mail->CharSet = "UTF-8";
                    $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
                    $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
                    $mail->From 	= 'noreply@ovs.it';
                    $mail->FromName = 'OVS Online Store';
                    $mail->Subject = "Errori pagamento paypal ".$this->order_number;

                    $mail->Body		= "Errore ".$reply['L_SHORTMESSAGE0'];

                    $mail->addAddress($email_address);

                    $mail->addBCC('alberto.botti@h-farm.com');
                    $mail->addBCC('michele.fabbri@h-farm.com');
                    $mail->send();
                }

                if ($errorCode)
                    return false; //TODO Rino 08/07/2016   Ho gestito diversamente gli errori e lo gli status

                return true;

            } catch (SoapFault $exception) {
                $this->log->LogError("Errore pagamento: ".$exception);
                //var_dump(get_class($exception));
                //var_dump($exception);
                $manager->updateDoCaptureResponse('9999', $exception->getMessage());
                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);
                // $magOrderHelper->setStatusPendingPayment($increment_id); //TODO Rino 08/07/2016   il set dello stato Pending payment viene fatto fuori
                MailSender::sendEmail("Attenzione errore pagamento: ".$this->order_number,'nomovs@gmail.com','Warning NOM');
                return false;

            }
        }

        $this->log->LogInfo("Payment: ".$this->order_number." OK");
        return true;
    }

    public function executeIncassoManuale() {
        $manager = new PaymentDBHelper($this->order_number);
        $paymentObj = $manager->getPaymentInfo();
        //echo "\nProcessing payment: ".$this->order_number;
        //print_r($paymentObj);
        if (!$paymentObj) {
            //echo "\nErrore nessun payment object";
            $this->log->LogError("Errore nessun payment info per ordine:".$this->order_number);
            return false;
        }
        $this->log->LogInfo("Payment: ".$this->order_number);
        //print_r($paymentObj);




        //tutto il pezzo fino alla return è solo per il test
        //echo "\nSetting Mangento Status TEST";
        $magOrderHelper = new MagentoOrderHelper();
        $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);
        //$magOrderHelper->setStatusComplete($increment_id);

        //devo prendere il valore dall'ordine perchè in caso di reso è diverso il valore dal paymentinf
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');
        $orderValue = $order->getBaseGrandTotal();

        $paymentObj->amount = round ($orderValue,2);
        $this->log->LogInfo("Payment Amount: $orderValue (".$this->order_number.")");

        $payment = $order->getPayment();
        $payment_method_selected = $payment->getMethod();
        //print_r($payment->getData());
        //echo "\nPayment Method: ".$payment_method_selected;
        if ($payment_method_selected=='cashondelivery') {
            //Contrassegno
            $this->log->LogInfo("Payment: ".$this->order_number." OK CONTRASSEGNO");
            return true;
        }

        //$reasonCode = "DUMMY100";
        //$decision = "FAKE100";
        //$manager->updateDoCaptureResponse($reasonCode, $decision);
        //return true;


        if ($paymentObj->payment_method=='CYBERSOURCE') {
            $pgw = new CybersourceProcessor(array());
            try {
                //TODO GESTIONE AMOUNT PARZIALI andando a prendere il valore dell'ordine che potrebbe essere stato modificato dal magazziniere
                $reply = $pgw->doCapture($paymentObj->ref_code, $paymentObj->auth_token, $paymentObj->auth_id, $paymentObj->amount);
                $reasonCode = $reply->reasonCode;
                $decision = $reply->decision;
                $this->log->LogDebug("Reason Code: ".$reasonCode);
                $this->log->LogDebug("Decision Code: ".$decision);
                $manager->updateDoCaptureResponse($reasonCode, $decision);

                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);

                //TODO SISTEMARE GESTIONE ERRORI PAGAMENTI
                $magOrderHelper->setStatusComplete($increment_id);

                $comment = "Capture  Result: [$reasonCode,$decision]";
                $closed = $reasonCode==100 ? 1: 0;
                //$magOrderHelper->setTransaction($increment_id, $paymentObj->auth_id, $paymentObj->auth_id, "authorize_capture", $comment, $closed);

                return true;

            } catch (SoapFault $exception) {
                $this->log->LogError ("Errore pagamento: ".$exception);
                //var_dump(get_class($exception));
                //var_dump($exception);
                $manager->updateDoCaptureResponse('9999', $exception->getMessage());
                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);
                $magOrderHelper->setStatusPendingPayment($increment_id);
                $comment = "Capture fallita [999,".$exception->getMessage()."]";
                $closed = 0;
                //$magOrderHelper->setTransaction($increment_id, $paymentObj->auth_id, $paymentObj->auth_id, "authorize_capture", $comment, $closed);
                MailSender::sendEmail("Attenzione errore pagamento: ".$this->order_number,'nomovs@gmail.com','Warning NOM');
                return false;
            }

        } else {

            $pgw = new PayPalProcessor();
            try {
                //TODO GESTIONE AMOUNT PARZIALI andando a prendere il valore dell'ordine che potrebbe essere stato modificato dal magazziniere
                $reply = $pgw->doCapture($paymentObj->auth_id, $paymentObj->amount);
                $errorCode = $reply['L_ERRORCODE'];
                if ($errorCode) {
                    $reasonCode = $errorCode;
                    $decision = $reply['L_SHORTMESSAGE'];
                } else {
                    $reasonCode = "None";
                    $decision = "None";
                }

                $this->log->LogDebug ("Error Code: ".$errorCode);
                $this->log->LogDebug ("Reason Code: ".$reasonCode);
                $this->log->LogDebug("Decision Code: ".$decision);

                $manager->updateDoCaptureResponse($reasonCode, $decision);

                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);
                if ($errorCode) {
                    $this->log->LogDebug ("Set Pending");
                    $magOrderHelper->setStatusPendingPayment($increment_id); //TODO verificare se effettivamente deve rimanere così come stato
                }
                else {
                    $this->log->LogDebug ("Set Complete");
                    $magOrderHelper->setStatusComplete($increment_id);
                }


                return true;

            } catch (SoapFault $exception) {
                $this->log->LogError("Errore pagamento: ".$exception);
                //var_dump(get_class($exception));
                //var_dump($exception);
                $manager->updateDoCaptureResponse('9999', $exception->getMessage());
                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($this->order_number);
                $magOrderHelper->setStatusPendingPayment($increment_id);
                MailSender::sendEmail("Attenzione errore pagamento: ".$this->order_number,'nomovs@gmail.com','Warning NOM');
                return false;

            }
        }

        $this->log->LogInfo("Payment: ".$this->order_number." OK");
        return true;
    }


    public function authorize($ref_code, $amount) {
        try {
            $pgw = new CybersourceProcessor(array());

            $reply = $pgw->authorize($ref_code, $amount);


        } catch (SoapFault $exception) {
            var_dump(get_class($exception));
            var_dump($exception);
        }
    }

}


//$t = new PaymentProcessor('00257982');
//$t->executePayment();
//$t->doRefund("20.00");
//t->getTransactionDetails();
