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
				border-bottom: 1px solid #CCCCCC;
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
				border: 1px solid #CCCCCC;
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
				margin-bottom:20px;
			}
			
			#top {
				margin-bottom: 0;
			}
			
			hr {
				border: none;
				margin-bottom: 20px;
				height: 1px;
				background-color: #CCCCCC;
			}
			
			@page { margin: 130px 30px 150px 30px}
		
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
		</style>
	</head>
 
	<body>
		<div id="header" style="color:#a1a1a1; font-size:10px">
			<!-- Header -->
			<table width="100%" id="top">
				<tr>					
					<td align="center" valign="middle">
						<img src="http://shop.dreamcharme.com/logo.jpg" alt="Dream&Charme" width="200" />
					</td>
				</tr>
			</table>
			<!-- .Header -->	
		</div>
		<div id="footer">
			<table style="font-size:10px; margin-top: 4px; color:#a5a5a5" width="100%">
				<tr>
					<td valign="top" width="20%" style="font-size: 12px; color:#494948">
						<strong>CONSULNET ITALIA S.r.l.</strong>
					</td>
					<td valign="top" width="18%">
						<strong>Sede legale</strong><br/>
						Via del Lauro, 2<br/>
						20121 - Milano - Italia<br/>
						P.IVA: 11702130151<br/>
						Cap. Soc. 10.400,00 €<br/>
						REA: 1489151<br/>
						Trib. Milano: 359989
					</td>
					<td valign="top" width="18%">
						Tel: +39.02.80503457<br/>
						+39.02.89011933<br/>
						Fax: +39.02.89011774
					</td>
					<td valign="top" width="20%">
						<a href="mailto:support@dreamcharme.com">support@dreamcharme.com</a><br/>
						<a href="shop.dreamcharme.com">shop.dreamcharme.com</a>
					</td>
					<td align="right" valign="top" width="18%">
						<img src="http://shop.dreamcharme.com/logo.jpg" alt="Dream&Charme" width="110" />
					</td>
				</tr>
			</table>

		</div>
		<hr/>
		<br/><br/>
		<table width="85%" align="center" style="border-spacing: 10px !important; border-collapse: separate;">
			<tr>
				<td style="font-size:20px; background-color: white" width="50%" valign="top">Fattura n. <?php echo $header_fattura['numero_fattura']; ?><br/>
					<span style="font-size: 17px">del <?php echo $header_fattura['data_fattura']; ?></span><br/>
					<span style="font-size: 15px">Rif. Ordine: <?php echo $header_fattura['numero_ordine']; ?></span><br/>
					</td>
				</td>
				<td width="50%" valign="top" align="left" >
					<div style="background-color: #f6f6f6; padding: 10px; margin-right: -10px">
						Spett.le:
						<br />
						<?php echo $header_fattura['billing_firstname']." ".$header_fattura['billing_lastname']; ?><br/>
						<?php echo $header_fattura['billing_company']; ?><br/>
						<?php echo $header_fattura['billing_street']; ?><br/>
						<?php 
							echo $header_fattura['billing_city']; 
							if($header_fattura['billing_region_code']) {
								echo " (".$header_fattura['billing_region_code'].")";
							}
							echo " - " .$header_fattura['billing_post_code']; 
						?><br/>
						<?php 
							if($header_fattura['billing_vat_id'] != "") {
								echo "P. Iva: ". $header_fattura['billing_vat_id']. "<br/>";
							}
							if($header_fattura['billing_cf'] != "") {
								echo "C.F.: ". $header_fattura['billing_cf'];
							}
						?>
					</div>
				</td>
			</tr>
				
		</table>
		<br/><br/>

		
		<table width="85%" align="center" style="border-spacing: 10px !important; border-collapse: separate; background-color: #f6f6f6">
			<tr>
				<th align="left">Descrizione</th>
				<th align="right">Q.ta</th>
				<th align="right">Prezzo</th>
				<th align="right">Totale</th>
			</tr>
			
			<?php 
				// Stampa dettaglio fattura
				foreach($dettaglio_fattura as $prodotto) {
			?>
			<tr>
				<td align="left"><?php echo $prodotto['YI6DESC1']; ?></td>
				<td align="right"><?php echo $prodotto['YI6QUANT']; ?></td>
				<td align="right"><?php echo number_format($prodotto['YI6PREZZ'], 2); ?></td>
				<td align="right"><?php echo number_format($prodotto['YI6VALOR'], 2); ?></td>
			</tr>	
			<?php	
				}
			?>
			
			<?php 
				// Stampa informazioni extra
				foreach($extra_info as $info) {
			?>
			<tr>
				<td align="left" colspan="3"><?php echo $info['YI6DESC1']; ?></td>
				<td align="right"><?php echo number_format($info['YI6VALOR'], 2); ?></td>
			</tr>	
			<?php	
				}
			?>
			
		</table>

		<br/><br/>

		<table width="85%" align="center">
					
			<tr>
				<td valign="top" width="50%">Totale Ordine<br/></td>
				<td valign="top" align="right"><strong><?php echo $header_fattura['imponibile']; ?> €</strong></td>
			</tr>
			<tr>
				<td valign="top" >IVA<br/></td>
				<td valign="top" align="right" style="border-bottom: 1px solid #CCCCCC"><strong><?php echo $header_fattura['iva']; ?> €</strong></td>
			</tr>
			<tr>
				<td valign="top"><strong>Totale Fattura</strong><br/><br/></td>
				<td valign="top" align="right"><strong><?php echo $header_fattura['valore_ordine']; ?> €</strong></td>
			</tr>			
		</table>
	</body>
</html>

