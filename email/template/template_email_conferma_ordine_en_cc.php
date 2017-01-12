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

    <p>Gentle <?php echo $info_cliente['firstname']." ".$info_cliente['lastname']; ?>,</p>

    <p>This is to confirm that the order number <?php echo $info_ordine->ordine; ?> it was sent at the store <?php echo $info_ordine->negozio; ?></p>

    <p>You will receive an email when your order will arrive at the store and you can withdraw it.</p>

    <p>From tomorrow you can track the shipment on the shipper's web site by linking to:</p>

    <p><?php echo '<a href="'.$info_ordine->tracking_url.'">'.$info_ordine->tracking_url.'</a>'; ?></p>

    <p>
    Print the receipt attached and take it with you in the store to pick up the order.<br/>
    For more information about products or suggestions for your next purchase visit www.ovs.it<br/>
    or contact Customer Service via the "ecommerce help" of the site.</p>
    </p>

    <p align="right">Thank you for choosing OVS.</p>

    <p align="right">Our best regards,</p>

    <p align="right"><em>Customer Service, OVS.</em></p>

</body>
</html>