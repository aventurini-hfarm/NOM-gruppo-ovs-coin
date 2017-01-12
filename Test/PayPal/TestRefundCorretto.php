<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 03/08/15
 * Time: 11:43
 */
require_once "/home/OrderManagement/paymentgw/PaymentProcessor.php";

class TestRefundCorretto {

    public function eseguiRimborsoPayPal() {

        $t = new PayPalProcessor();
        $res = $t->doRefund('55U16146R99109251','10.17');
        print_r($res);

    }


    public function eseguiRimborsoManuale() {

        $t = new PaymentProcessor('00162259');

        $t->doRefund('10.17');


    }

}

$c = new TestRefundCorretto();
$c->eseguiRimborsoManuale();
