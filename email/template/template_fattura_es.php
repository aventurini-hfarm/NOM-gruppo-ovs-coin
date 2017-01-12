<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Stampa Fattura</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica,sans-serif;
				font-size: 13px;
				word-break:break-all;
			}
			
			th {
				border-bottom: 1px solid;
			}
			
			table {
				border-spacing: 10px;
				border-collapse: collapse;
			}
			
			
			td, th {
				padding: 3px;
			}
			
			tr {

			}
			
			#top td {
				padding: 0;
			}
			
			#summary td, #partenza td, #destinazione td, #list td {
				border: 1px solid ;
				border-collapse: collapse;
			}
			
			h2 {
				text-align: center;
				background-color: #505050;
				color: white;
				padding: 5px;
				font-weight: normal;
				font-size:15px;
			}
			
			table {
				margin-bottom:5px;
			}
			
			#top {
				margin-bottom: 0;
			}
			
			hr {
				border: none;
				margin-bottom: 20px;
				height: 1px;
				/*background-color: #CCCCCC;*/
			}

			@page { margin: 130px 30px 30px 30px}

		
			#header { 
				position: fixed; 
				left: 0px; 
				top: -100px;
				right: 0px; 
				height: 180px; 
				text-align: left; 
			}

			#footer { 
				position: fixed; 
				left: 0px; 
				bottom: -180px; 
				right: 0px; 
				height: 150px;
				border-top: 1px solid #CCCCCC;
				/* background-color: #f9f9f9; */ 
			}
			
			a {
				color: #000000;
			}
			
			a:hover {
				color: #000000;
			}

			.with-border {
				border: solid 1px black;
			}
            .with-side-border {
                border-left: solid 1px black;
                border-right: solid 1px black;
                border-top: none;
                border-bottom: none;
            }

            .separator {
				padding: 10px;
				border-bottom: solid 1px;
			}

		</style>
	</head>
 
	<body>
		<div id="header" style="font-size:9px;">
			<!-- Header -->
			<table width="100%" id="top" style="margin-top: 4px;" >
				<tr>					
					<td  width="60%" valign="middle">
						<img src="/home/OrderManagement/email/template/logo_ovs.png" alt="Ovs" align="middle" style="padding: 0" />
					</td>
					<td valign="top" width="40%">
						OVS SpA con accionista &uacute;nico<br/>
						Sede jurídica y administrativa: Via Terraglio 17, 30174, Mestre, Venecia (Italia)<br/>
						Tel.: + 39 041 2397500 Fax: +39 041 2397630<br/>
						N&uacute;mero de inscripci&oacute;n en el Registro de Empresas italiano de Venecia,<br/>
						C&oacute;digo de impuestos y n&uacute;mero de IVA 04240010274<br/>
						Capital social Euros 227.000.000,00 totalmente desembolsado.<br/>
					</td>
				</tr>
				<tr>
					<td  width="60%" >
					</td>
					<td>
						<?php if ($infoFiscale->rapprFiscale) { ?>
                            <?php echo $infoFiscale->header; ?>
                        <?php } ?>
					</td>
				</tr>
			</table>
			<!-- .Header -->	
		</div>
        <br/>

        <div id="testata_fattura">
            <table style="font-size:10px; margin-top: 4px; " width="100%" align="center">
                <tr>
                    <td>
						FACTURA: <?php echo $infoFiscale->codice_ente.'0/'.$infoFiscale->registro_iva.$info_ordine->num_fattura; ?><br/>
						FECHA: <?php echo $info_ordine->data_documento_fattura; ?><br/>
						<?php if ($info_ordine->cf) {?>
							Codigo Fiscal: <?php echo $info_ordine->cf; ?><br/>
						<?php } if ($info_ordine->piva) {?>
							VAT Number: <?php echo $info_ordine->piva; ?><br/>
						<?php }?>
                    </td>
                </tr>
            </table>
        </div>

        <br/><br/>
		<div id="testata">
			<table style="font-size:10px; margin-top: 4px; " width="100%" align="center">
				<tr>
					<td valign="top" width="25%">
						CLIENTE<br/><br/>
						<?php echo $info_ordine->rag_sociale_nome." ".$info_ordine->rag_sociale_cognome; ?><br/>
						<?php echo $info_cliente['street']; ?><br/>
						<?php echo $info_cliente['postcode']." ".$info_cliente['city']." ".$info_cliente['region'];?><br/>
                        <?php echo $info_cliente['country_id']; ?><br/>
                        <?php if ($info_ordine->cf) {
                            echo $info_ordine->cf;
                        } else {
                            echo $info_ordine->piva;
                        } ?><br/>

					</td>

					<td valign="top" width="25%">
						INFORMACI&Oacute;N DE LA COMPRA<br/><br/>
						Cliente: <?php echo $info_ordine->cliente; ?><br/>
						Recibo: <?php echo $info_ordine->scontrino; ?><br/>
						Fecha: <?php echo $info_ordine->data_documento; ?><br/>
						Pedido: <?php echo $info_ordine->ordine; ?><br/>
						Fecha de compra:<?php echo $info_ordine->data_ordine; ?> <br/><br/>
						Tel&eacute;fono: <?php echo $info_ordine->telefono; ?><br/>
						Email: <?php echo $info_ordine->email; ?>

					</td>
					<td valign="top" width="25%">
						INFORMACI&Oacute;N DESTINATARIO<br/><br/>
                        Tel&eacute;fono: <?php echo $info_destinatario->getTelephone(); ?><br/>
						
					</td>
					<td valign="top" width="25%">
						DIRECCI&Oacute;N DE ENV&Iacute;O<br/><br/>
                        <?php echo $info_destinatario->firstname." ".$info_destinatario->lastname; ?><br/>
                        <?php echo $info_destinatario->street; ?><br/>
                        <?php echo $info_destinatario->postcode." ".$info_destinatario->city." ".$info_destinatario->region;?><br/>
                        <?php echo $info_destinatario->country_id; ?><br/>
						
					</td>
				</tr>
			</table>

		</div>
		
		<br/><br/>

		
		<table width="100%" align="center" style="font-size:10px; font-weight: normal; border-spacing: 10px !important;  border: 1px solid;" >
			<tr>
				<th align="left" style="font-weight: normal;">REF.</th>
				<th align="left" style="font-weight: normal;">DESCRIPCI&Oacute;N</th>
				<th align="right" style="font-weight: normal;">CANT.</th>
				<th align="center" class="with-border" style="font-weight: normal;">PRECIO EUR</th>
				<th align="center" class="with-border" style="font-weight: normal;">% DS.</th>
				<th align="center" class="with-border" style="font-weight: normal;">DS. PRECIO EUR</th>
				<th align="center" class="with-border" style="font-weight: normal;">IVA%</th>
				<th align="center" style="font-weight: normal;">TOTAL EUR</th>
			</tr>
			
			<?php 
				// Stampa dettaglio fattura
				$contatore_righe=0;

				foreach($items as $item) {
					$contatore_righe++;
			?>
			<tr>
				<td align="left" style="font-size:9px;"><?php echo $item->codice; ?></td>
				<td align="left" style="font-size:9px;"><?php echo $item->descrizione; ?></td>
				<td align="right" style="font-size:9px;"><?php echo number_format($item->qty,0); ?></td>
				<td align="right" class="with-side-border" style="font-size:9px;"><?php echo number_format($item->unit_price, 2); ?></td>
				<td align="right" class="with-side-border" style="font-size:9px;"></td>
				<td align="right" class="with-side-border" style="font-size:9px;"><?php echo number_format($item->unit_discount_price, 2); ?></td>
                <td align="right" class="with-side-border" style="font-size:9px;"><?php if ($item->total) echo substr($infoFiscale->iva,2);?></td>
				<td align="right" class="with-side-border" style="font-size:9px;"><?php echo number_format($item->total, 2); ?></td>

			</tr>	
			<?php	
				}
			?>
				
			<!--aggiunge righe vuote per la size tabella corretta-->
			<?php
				$max_righe_tabella=40;
				while ($contatore_righe < $max_righe_tabella ) {
					$contatore_righe++; ?>
					<tr>
                        <td align="left" style="font-size:9px;"></td>
                        <td align="left" style="font-size:9px;"></td>
                        <td align="right" style="font-size:9px;"></td>
                        <td align="right" class="with-side-border" style="font-size:9px;"></td>
                        <td align="right" class="with-side-border" style="font-size:9px;"></td>
                        <td align="right" class="with-side-border" style="font-size:9px;"></td>
                        <td align="right" class="with-side-border" style="font-size:9px;"></td>
                        <td align="right" class="with-side-border" style="font-size:9px;"></td>

                    </tr>
				<?php }
			?>

			<tr style="background: #CCCCCC">
				<td align="left" class="with-side-border" colspan="3" style="font-size:9px;">COSTE DEL ENV&Iacute;O</td>
				<td align="right" class="with-side-border" style="font-size:9px;"><?php echo number_format($shipping_charge->shippingAmount, 2); ?></td>
                <td align="right" class="with-side-border" style="font-size:9px;"></td>
				<td align="right" class="with-side-border" style="font-size:9px;"><?php echo number_format($shipping_charge->shippingValoreScontato, 2); ?></td>
                <td align="right" class="with-side-border" style="font-size:9px;"><?php echo number_format($shipping_charge->total)==0 ? "": substr($infoFiscale->iva,2); ?></td>
				<td align="right" style="font-size:9px;"><?php echo number_format($shipping_charge->total, 2); ?></td>
			</tr>	

		</table>

		<br/>


<!-- blocco codice a barre e e totale-->
		<table width="100%" align="center" style="font-size:9px; border-spacing: 1px;">
					
			<tr>

                <td class="with-border">
                    <table width="100%" style="border-spacing: 1px; padding: 2px;">
                        <tr width="100%">
                            <td valign="" align="center" width="100%">
                                <img width="145" src="/home/OrderManagement/email/template/codice_barre_ovs.png"/><br>
                                <?php if ($info_ordine->barcode) {?>
                                    <img alt="<?php echo $info_ordine->barcode; ?>" src="http://<?php echo $host; ?>/php-barcode-master/barcode.php?text=<?php echo $info_ordine->barcode; ?>&print=true&size=50&codetype=code25" />
                                <?php } ?>
                            </td>
                        </tr>
                        <tr style="font-size:9px;">
                            <td>
                                <?php echo $infoFiscale->footer; ?>
                            </td>
                        </tr>
                    </table>
                </td>
				<td valign="top" width="2%" ></td>
				<td valign="top" width="35%" class="with-border">
					<table  width="100%">
						<tr>
							<td width="70%" >IMPONIBLE:</td>
							<td width="30%"  align="right"><?php echo number_format($info_ordine->imponibile ,2); ?><br/></td>
						</tr>	
						<tr>
							<td width="70%">IVA: <?php echo substr($infoFiscale->iva,2);?>% INCL SPAIN</td>
							<td width="30%" align="right"><?php echo number_format($info_ordine->iva ,2); ?><br/><br/></td>
						</tr>	
						<tr>
							<td width="70%"  valign="center">TOTAL EUR:</td>
							<td width="30%"  valign="center" align="right"><?php echo number_format($info_ordine->amount ,2); ?><br/><br/><br/><br/></td>
						</tr>	
						<tr>
							<td colspan="2"><?php echo "Pago: ". $payment->description_line1; ?></td>
						</tr>	

						<tr>
							<td colspan="2"><?php echo $payment->description_line2; ?></td>
						</tr>	
					</table>	
				</td>
			</tr>
		</table>

	</body>
</html>
