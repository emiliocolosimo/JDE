<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

/**
 * This example shows how to send via Microsoft Outlook's servers using XOAUTH2 authentication
 * using the league/oauth2-client to provide the OAuth2 token.
 * To use a different OAuth2 library create a wrapper class that implements OAuthTokenProvider and
 * pass that wrapper class to PHPMailer::setOAuth().
 */

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;
//Alias the League Google OAuth2 provider class
use Greew\OAuth2\Client\Provider\Azure;

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Etc/UTC');

//Load dependencies from composer
//If this causes an error, run 'composer install'
require 'vendor/autoload.php';

//Create a new PHPMailer instance
$mail = new PHPMailer();
 

// Set the mailer to use SMTP
$mail->isSMTP();

$mail->Port = 587;

$mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;

// Set the SMTP server
$mail->Host = 'smtp.office365.com';

// Enable SMTP authentication
$mail->SMTPAuth = true;

// Set the SMTP authentication type (Microsoft OAuth 2.0)
$mail->AuthType = 'XOAUTH2';

// Set the OAuth 2.0 access token
$mail->oauthUserEmail = 'smtpas400@rgpballs.com';
$mail->oauthClientId = '7982c594-1dbf-4143-9a44-38149cb100fb';
$mail->oauthClientSecret = 'hnr8Q~j_24vK8tCzOozJfZaUJTRDscQbt.uS.arc';
$mail->oauthRefreshToken = '0.AUcAGVlYMiB3QkSr2oV9TGGAXJTFgnm_HUNBmkQ4FJyxAPsNAX0.AgABAAEAAADnfolhJpSnRYB1SVj-Hgd8AgDs_wUA9P98iBzLGjyfpUzWQP_VqBUMXbt-zVHjB6bO3ZPqNkeTpoZPCb7cYrMeQUGX6P2sudyRGARlkma_jYUXhoCbvtQfXEuPWQXs7mkEpjCiJ0g6SXwwRsdBr7lTOyeOxlB4hx2PGxy7acMWyOdJhO2zyxlFqvuoHIxLIIlpCzjORdFdbMw0jyb-x4_GrslCNzJvJ-vCBiiDJr1SwQnToZ2aFVG3XVnMTdEb31ZOJ0QW1hLL-joQ1QCsGus0sHyKti_AvMGel1hHS3I-634M2neCJI34jFVSpE_UGdeDtLdONL9PY33HNLvbn6ypWbPqp2zKl66xYa-wnyJz9pS5zmBXT8yBPrEv2s8-OntItGulMnS3tmGDX2GubtDiErF1Clai4Nsq-YgZU6TArHgLt_L8oGIAJi-vQ7GP1CXsQphhbVeDiKCBe7YcKCQi7lgFJ_t55l0JDxujaEsdtBTDYjfnR1b6QtI4f3liDpOpjNBqA1VGPqOXEck0LjbFmfdCpuOZTuGRaeb-bXSNhVJvEattqOQqQSHd6sUD5uJXmhIhuq1-gf0bV8sY74VpCZ7TbVZU5qAb2KHjoUbFK9xsB7RXJonoLV3FJhQWtZJLmLD6kyRIfSKBzPSn-3UwIcXwM53WnAbgM9WPjhPdeXHwUHFjbfk3n3qEudqeWexNNbbkNuAvofYIXbn6k2rKLnVDTEjCTswgX2DYDDuLYmuu';

// Set the email parameters
$mail->setFrom('smtpas400@rgpballs.com', 'Your Name');
$mail->addAddress('mattia.marsura@bigblue.it', 'Recipient Name');
$mail->Subject = 'prova';
$mail->Body = 'prova';

// Send the email
$res = $mail->send();
var_dump($res);
exit;

/*************************************************************/

//Tell PHPMailer to use SMTP
$mail->isSMTP();

//Enable SMTP debugging
//SMTP::DEBUG_OFF = off (for production use)
//SMTP::DEBUG_CLIENT = client messages
//SMTP::DEBUG_SERVER = client and server messages
$mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;

//Set the hostname of the mail server
$mail->Host = 'smtp.office365.com';

//Set the SMTP port number:
// - 465 for SMTP with implicit TLS, a.k.a. RFC8314 SMTPS or
// - 587 for SMTP+STARTTLS
$mail->Port = 587;

//Set the encryption mechanism to use:
// - SMTPS (implicit TLS on port 465) or
// - STARTTLS (explicit TLS on port 587)
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

//Whether to use SMTP authentication
$mail->SMTPAuth = true;

//Set AuthType to use XOAUTH2
$mail->AuthType = 'XOAUTH2';

//Start Option 1: Use league/oauth2-client as OAuth2 token provider
//Fill in authentication details here
//Either the microsoft account owner, or the user that gave consent
$email = 'smtpas400@rgpballs.com';
$clientId = '7982c594-1dbf-4143-9a44-38149cb100fb';
$clientSecret = 'hnr8Q~j_24vK8tCzOozJfZaUJTRDscQbt.uS.arc';
$tenantId = '32585919-7720-4442-abda-857d4c61805c';

//Obtained by configuring and running get_oauth_token.php
//after setting up an app in Google Developer Console.
$refreshToken = '0.AUcAGVlYMiB3QkSr2oV9TGGAXJTFgnm_HUNBmkQ4FJyxAPsNAX0.AgABAAEAAADnfolhJpSnRYB1SVj-Hgd8AgDs_wUA9P-rO9xF_-FtGRo89S2nP9pLZ3-MK5aiSYDlOHbFgGx7Gwlc0z225VFhiFfdmCd4uc14SHT1rU8qDUZHY8JpiFwtToKVDFKdftS1Xysb_7V9CmYIvFUnN6FfmtflnMo2AANtzIFN8r8AozF987RrQyTJdgSCwqZ5lUIKYPHVH1QhBuL689yRPdJuQV_lYzRLQbPxRdD8tkUPqXIT8DjnBu1JOPJ5XNSGwtrQTgVI7UKpUFF6CPmxZuo5IpV-9rW7OKv_CZw65lm0I2XDfwzoAy0dZMaYRR_aco2TUOHKgU_FDsYHuJxSkvX5dD62biVfdrjHIF2B8yhTwQQU4T-fWco3Jq81jnT4OHbqCtlTK9SCoaHIUzlmFekPstq9hQKESQLGWjBI70g5dtJ_50ZBJwXHiX_ztOE68NSYxWcv610xxTsN9lGeW1OPUyb0tv7i0glfRQBtEmpCJ_GLAskZDhqOH0c_oyDOV79Vnpjlg_jQxQmOu6M0lFo7XoRTMMuGu-ArJueymJ9R2mkHoW4zrd9z2u1yAn0qUerTCvuKy4ECj8JohOQwOmp_C6ifMoP7qEUUlWvN_K7aMSH4nJ_48WnA_66Lwdrp5rk9ajdcYf74l6GPa-qW85vOfEwZTRZ0LlwIIx7IRHK5WreUBKFqqxiXhhCpJ3k10zVeRuPd8OkT1RcCF2-doiZNwJscW6_VvC-ZWEqvsgd_9twy';

//Create a new OAuth2 provider instance
$provider = new Azure(
    [
        'clientId' => $clientId,
        'clientSecret' => $clientSecret,
        'tenantId' => $tenantId
    ]
);

//Pass the OAuth provider instance to PHPMailer
$mail->setOAuth(
    new OAuth(
        [
            'provider' => $provider,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'refreshToken' => $refreshToken,
            'userName' => $email
        ]
    )
);
//End Option 1



//Set who the message is to be sent from
//For Outlook, this generally needs to be the same as the user you logged in as
$mail->setFrom($email, 'First Last');

//Set who the message is to be sent to
$mail->addAddress('mattia.marsura@bigblue.it', 'Mattia Marsura');

//Set the subject line
$mail->Subject = 'PHPMailer Outlook XOAUTH2 SMTP test';

//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
$mail->CharSet = PHPMailer::CHARSET_UTF8;
$mail->msgHTML("prova di invio mail");

//Replace the plain text body with one created manually
$mail->AltBody = 'This is a plain-text message body';


//send the message, check for errors
if (!$mail->send()) {
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message sent!';
}
