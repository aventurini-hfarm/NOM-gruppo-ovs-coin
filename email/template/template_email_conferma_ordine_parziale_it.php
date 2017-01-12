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

    <p>Gentile <?php echo $info_cliente['firstname']." ".$info_cliente['lastname']; ?>,</p>

    <p>per motivi logistici, l'ordine numero  <?php echo $info_ordine->ordine; ?> le verrà consegnato con due spedizioni diverse.</p>

    <p>Da domani potr&agrave; seguire la spedizione sul sito del corriere al seguente link:</p>

    <p><?php echo '<a href="'.$info_ordine->tracking_url.'">'.$info_ordine->tracking_url.'</a>'; ?></p>

    <p>Le invieremo una nuova mail, con il link di tracciamento della seconda spedizione, nel momento in cui affideremo al corriere i restanti articoli del suo ordine.</p>
    <p>Per ulteriori informazioni sui prodotti o per suggerimenti sui tuoi prossimi acquisti visita <a href="http://<?php echo $info_ordine->link_sito; ?>"><?php echo $info_ordine->link_sito; ?></a>  oppure contatta il Servizio Clienti tramite la sezione &quot;e-commerce help&quot; del sito.</p>

    <br>

    <p align="right">Grazie per avere scelto OVS.</p>

    <p align="right">I nostri pi&ugrave;  cordiali saluti,</p>

    <p align="right"><em>Il Servizio Clienti e-Commerce OVS</em></p>

</body>
</html>