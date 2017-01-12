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

        <p>Ti confermiamo che l'ordine numero <?php echo $info_ordine->ordine; ?> &egrave; stato ricevuto presso il negozio <?php echo $info_ordine->negozio; ?><br/>
        il <?php echo $info_ordine->data_ricezione_negozio; ?> ed &egrave; possibile ritirarlo.<br/>
        </p>

        <br/>
        <?php echo $info_ordine->negozio; ?><br/>
        <?php echo $info_ordine->indirizzo_negozio; ?> <?php echo $info_ordine->negozio_paese; ?> <?php echo $info_ordine->country; ?><br/>
        <?php echo $info_ordine->orario_negozio; ?><br/>


        <p>Ti ricordiamo di portare con te la stampa della ricevuta allegata alla mail di conferma spedizione e un <strong>documento di identit&agrave;</strong>.<br/><br/>
        <strong>Nel caso il pacco sia ritirato non da te personalmente ma da una persona da te delegata, tale persona dovr&agrave; presentarsi<br/> con una delega scritta e fotocopia del tuo documento d'identit&agrave;,oltre alla stampa della ricevuta allegata alla mail di conferma spedizione.</strong><br><br/>
        Per ulteriori informazioni sui prodotti o per suggerimenti sui suoi prossimi acquisti visita www.ovs.it<br/>
        oppure contatta il Servizio Clienti tramite la sezione "ecommerce help" del sito.<br/>
        </p>

        <br>
        <br>

        <p align="right">Grazie per avere scelto OVS.</p>
        <p align="right">I nostri pi&ugrave;  cordiali saluti,</p>
        <p align="right"><em>Il Servizio Clienti e-Commerce OVS</em></p>

</body>
</html>