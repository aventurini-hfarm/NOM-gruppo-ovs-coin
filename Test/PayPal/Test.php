<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 15/06/15
 * Time: 09:11
 */

require __DIR__  . '/../../PayPal-PHP-SDK/autoload.php';
// After Step 1
$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        'AYSq3RDGsmBLJE-otTkBtM-jBRd1TCQwFf9RGfwddNXWz0uFU9ztymylOhRS',     // ClientID
        'EGnHDxD_qRPdaLdZz8iCr8N7_MzF-YHPTkjs6NKYQvQSBngp4PTTVWkPZRbL'      // ClientSecret
    )
);
