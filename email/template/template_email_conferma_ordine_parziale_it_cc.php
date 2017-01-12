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

    <p>per motivi logistici, l'ordine numero <?php echo $info_ordine->ordine; ?> sar&agrave;  spedito presso il negozio <?php echo $info_ordine->negozio; ?> con due spedizioni diverse.</p>

    <p>Riceverai una mail quando il tuo ordine arriver&agrave; presso il negozio e sar&agrave; possibile ritirarlo.</p>

    <p>Da domani potrai seguire la spedizione sul sito del corriere collegandoti a:</p>

    <p><?php echo '<a href="'.$info_ordine->tracking_url.'">'.$info_ordine->tracking_url.'</a>'; ?></p>

    <p>
        Ti invieremo una nuova mail, con il link di tracciamento della seconda spedizione, nel momento in cui affideremo al corriere i restanti articoli del tuo ordine.
    </p>
    <p>
    Per ulteriori informazioni sui prodotti o per suggerimenti sui suoi prossimi acquisti visita <a href="http://<?php echo $info_ordine->link_sito; ?>"><?php echo $info_ordine->link_sito; ?></a><br/>
    oppure contatta il Servizio Clienti tramite la sezione "ecommerce help" del sito.<br/>
    </p>

    <br/>

    <p align="right">Grazie per avere scelto Ovs.</p>

    <p align="right">I nostri pi&ugrave;  cordiali saluti,</p>

    <p align="right"><em>Il Servizio Clienti e-Commerce Ovs.</em></p>



</body>
</html>