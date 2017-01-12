<?php

date_default_timezone_set('Europe/Rome');

require_once "dompdf_config.inc.php";

function curl_file_get_contents($url) {
    $curl = curl_init();
    $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

    curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
    curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    $contents = curl_exec($curl);
    curl_close($curl);
    return $contents;
}



/*
	$nome = $wpUserData['first_name'];
    $cognome = $wpUserData['last_name'];
    $email = $wpUser->user_email;

    //$email			= "vincenzo.sambucaro@nuvo.it";
    //$email			= "fsaitta@aibu.it";
    $email = "davide_litrico@outlook.com";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=" . get_bloginfo('charset') . "" . "\r\n";
    $headers .= "From: Traslocabile.com <info@traslocabile.com>" . "\r\n";
    $template_url = WP_CONTENT_URL . '/plugins/traslocabile/emails/fattura.php?numero_fattura=' . $numero_fattura . "&anno_fattura=" . $anno_fattura . "&nome=" . $nome . "_" . $cognome;

    $message = file_get_contents($template_url);

    $subject = "La tua fattura";


    //$file_to_send = array(WP_CONTENT_DIR.'/uploads/fatture/fattura'.$numero_fattura.'.pdf');

    $invoicepath = WP_CONTENT_DIR . '/uploads/fatture/fattura' . $numero_fattura . '.pdf';
*/

	$doc = "http://shop.dreamcharme.com/pdf/template.php";
	$response['anno_fattura'] = "2014";
	$response['numero_fattura'] = "10";

	$invoicepath = '/var/www/fatture/'. $response['anno_fattura']."/". $response['numero_fattura'] . '.pdf';
	
    // Genera PDF e salva su filesystem
    $html = curl_file_get_contents($doc);


    $html = trim($html);
/*
    $html = preg_replace('~>\s+<~', '><', $html);

    $html = preg_replace('/src\s*=\s*"\//', 'src="' . home_url('/'), $html);
    $html = preg_replace('/src\s*=\s*\'\//', "src='" . home_url('/'), $html);
*/

    $filename = 'stampa' . '.pdf';

    $dompdf = new DOMPDF();
    $dompdf->set_paper("A4");

    // load the html content
    $dompdf->load_html($html);
    $dompdf->render();
    $canvas = $dompdf->get_canvas();

    $canvas->page_text(30, 810, "Pagina: {PAGE_NUM} di {PAGE_COUNT}", '', 8, array(0, 0, 0));
    
    
    // Salva PDF su filesystem
	$response['filesave'] = file_put_contents($invoicepath, $dompdf->output());
	$response['path'] = '/var/www/fatture/'. $response['anno_fattura']."/". $response['numero_fattura'] . '.pdf';
	
	//$response['mail'] = wp_mail($email, $subject, $message, $headers, array($invoicepath));
	//header('Content-Type: application/json');
	//echo json_encode($response);
	
	echo "<pre>";
	print_r($response);
	echo "</pre>";    
?>