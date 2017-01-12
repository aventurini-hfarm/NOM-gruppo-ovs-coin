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

        <p>Apreciado/a <?php echo $info_cliente['firstname']." ".$info_cliente['lastname']; ?>,</p>

        <p>Muchas gracias por haber comprado en OVS.</p>

        <p>Te confirmamos que hemos efectuado un reembolso de &euro;<?php echo $info_creditmemo->amount; ?> eferente alpedido n&uacute;mero <?php echo $info_ordine->ordine; ?>.</p>

        <p>
            DETALLES DEL PRODUCTO
            <table width="60%" style='font-weight: normal; border-spacing: 10px !important; border: 1px solid;'>
                <tr>
                    <th style='text-align: left; font-weight: normal;'>Cantidad</th>
                    <th style='text-align: left; font-weight: normal;'>C&oacute;digo del producto</th>
                    <th style='text-align: left; font-weight: normal;'>Descripci&oacute;n</th>
                    <th style='text-align: left; font-weight: normal;'>Pa&iacute;s</th>
                </tr>

                <?php
                // Stampa dettaglio fattura

                foreach($items as $item) {

                    ?>
                    <tr>
                        <td style='font-weight: normal;'><?php echo -1 * $item->qty; ?></td>
                        <td style='font-weight: normal;'><?php echo $item->codice; ?></td>
                        <td style='font-weight: normal;'><?php echo $item->descrizione; ?></td>
                        <td style='font-weight: normal;'>RESO</td>
                    </tr>
                <?php
                }
                ?>
            </table>
        </p>

        <br/>

        <p>
            El proceso de devoluci&oacute;n requiere alrededor de 2 d&iacute;as laborales para que el abono aparezca reflejado en tu tarjeta de cr&eacute;dito o tu cuenta de PayPal, en funci&oacute;n del tipo de pago que hayas utilizado. <br/>
            <br/>
            Si deseas m&aacute;s informaci&oacute;n, te invitamos a que contactes con el servicio de Atenci&oacute;n al Cliente a trav&eacute;s de la zona de Ayuda de nuestra p&aacute;gina web.
        </p>

        <br>

        <p align="right">Con nuestros m&aacute;s cordiales saludos,</p>

        <p align="right"><em>El servicio de Atenci&oacute;n al Cliente de OVS</em></p>

</body>
</html>