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

        <p>Caro/a <?php echo $info_cliente['firstname']." ".$info_cliente['lastname']; ?>,</p>

        <p>Ti ringraziamo della fiducia accordata a OVS.</p>

        <p>Ti confermiamo che &egrave; stato effettuato un rimborso pari a &euro;<?php echo $info_creditmemo->amount; ?> relativo all'ordine numero <?php echo $info_ordine->ordine; ?>.</p>

        <p>
            DETTAGLI PRODOTTO
            <table width="60%" style='font-weight: normal; border-spacing: 10px !important; border: 1px solid;'>
                <tr>
                    <th style='text-align: left; font-weight: normal;'>Qt&agrave;</th>
                    <th style='text-align: left; font-weight: normal;'>Codice Prodotto</th>
                    <th style='text-align: left; font-weight: normal;'>Descrizione</th>
                    <th style='text-align: left; font-weight: normal;'>Stato</th>
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
            La procedura di rimborso prevede circa 2 giorni lavorativi affinch&eacute; l'accredito sia visibile sulla tua carta di
            credito, oppure sul tuo conto Paypal, a seconda del tipo di pagamento che hai utilizzato.<br/>
            <br/>
            Se desideri ulteriori informazioni ti invitiamo a contattare il Servizio Clienti tramite la sezione "e-commerce
            help" del sito <a href="http://www.ovs.it">www.ovs.it</a>.
        </p>

        <br>

        <p align="right">I nostri pi&ugrave;  cordiali saluti,</p>

        <p align="right"><em>Il Servizio Clienti e-Commerce OVS</em></p>

</body>
</html>