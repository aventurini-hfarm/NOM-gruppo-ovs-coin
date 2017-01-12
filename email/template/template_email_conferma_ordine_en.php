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

    <p>We confirm you that the order number <?php echo $info_ordine->ordine; ?> has been shipped.</p>

    <p>Starting from tomorrow you will be able to track your shipping on the courier website from this link:</p>

    <p><?php echo '<a href="'.$info_ordine->tracking_url.'">'.$info_ordine->tracking_url.'</a>'; ?></p>

    <p>For further information on our products or for suggestions on your next purchases, please visit <a href="http://<?php echo $info_ordine->link_sito; ?>"><?php echo $info_ordine->link_sito; ?></a> or contact our Customer Service from the &quot;e-Commerce help&quot; section of our website.</p>

    <br>

    <p align="right">Thank you for choosing OVS.</p>

    <p align="right">Kindest regards,</p>

    <p align="right"><em>OVS e-Commerce Customer Service</em></p>

</body>
</html>