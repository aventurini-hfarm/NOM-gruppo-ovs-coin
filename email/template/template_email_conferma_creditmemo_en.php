<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Demystifying Email Design</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
</head>
<body style='margin: 0; padding: 30px; font-size: 16px; font-family: Arial;'>

    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr><td align="center"><img src='cid:logo_ovs'></td></tr>
    </table>

        <p>Dear <?php echo $info_cliente['firstname']." ".$info_cliente['lastname']; ?>,</p>

        <p>Thank you for choosing OVS.</p>

        <p>We confirm you that a reimbursement of &euro;<?php echo $info_creditmemo->amount; ?> for the order number <?php echo $info_ordine->ordine; ?> has been made.</p>

        <p>
            PRODUCT DETAILS
            <table width="60%" style='font-weight: normal; border-spacing: 10px !important; border: 1px solid;'>
                <tr>
                    <th style='text-align: left; font-weight: normal;'>Qty</th>
                    <th style='text-align: left; font-weight: normal;'>Product ID</th>
                    <th style='text-align: left; font-weight: normal;'>Description</th>
                    <th style='text-align: left; font-weight: normal;'>Status</th>
                </tr>

                <?php
                // Stampa dettaglio fattura

                foreach($items as $item) {

                    ?>
                    <tr>
                        <td style='font-weight: normal;'><?php echo -1 * $item->qty; ?></td>
                        <td style='font-weight: normal;'><?php echo $item->codice; ?></td>
                        <td style='font-weight: normal;'><?php echo $item->descrizione; ?></td>
                        <td style='font-weight: normal;'>RETURNED</td>
                    </tr>
                <?php
                }
                ?>
            </table>
        </p>

        <br/>

        <p>
            The reimbursement procedure will take approximately two working days. After that,
            you will obtain the credit on your credit card or on your Paypal account, depending on the kind of payment chosen.<br/>
            <br/>
            For further information on our products or for suggestions on your next purchases, please visit  <a href="http://www.ovs.it/en">www.ovs.it/en</a>
            or contact our Customer Service from the “e-Commerce help” section of our website.
        </p>

        <br>

        <p align="right">Kindest regards,</p>

        <p align="right"><em>OVS e-Commerce Customer Service</em></p>

</body>
</html>