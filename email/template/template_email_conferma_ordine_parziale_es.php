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

    <p>Estimado/a <?php echo $info_cliente['firstname']." ".$info_cliente['lastname']; ?>,</p>

    <p>Por razones logísticas, su pedido número <?php echo $info_ordine->ordine; ?> ser&aacute; entregado en dos envíos

        diferentes.</p>


    <p>A partir de ma&ntilde;ana, usted podr&aacute; localizar el primer env&iacute;o en el sitio web haciendo clic en este enlace:</p>

    <p><?php echo '<a href="'.$info_ordine->tracking_url.'">'.$info_ordine->tracking_url.'</a>'; ?></p>

    <p>Le enviaremos otro correo electrónico con el enlace para localizar el segundo env&iacute;o en cuanto los art&iacute;culos restantes sean entregados a la compa&ntilde;&ia de transportes.</p>

    <p>Para m&aacute;s informaci&oacute;n sobre los productos o para sugerencias sobre tus pr&oacute;ximas compras visita <a href="http://<?php echo $info_ordine->link_sito; ?>"><?php echo $info_ordine->link_sito; ?></a> o contacta con el servicio de Atenci&oacute;n al Cliente a trav&eacute;s de la zona de Ayuda de nuestra p&aacute;gina web.</p>

    <br>

    <p align="right">Gracias por haber elegido OVS.</p>

    <p align="right">Con nuestros m&aacute;s cordiales saludos,</p>

    <p align="right"><em>El servicio de Atenci&oacute;n al Cliente de OVS</em></p>

</body>
</html>