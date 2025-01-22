<?php
    include "vendor/autoload.php";

    use PHPMailer\PHPMailer\Exception;
    use PHPMailer\PHPMailer\OAuth;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
    
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = "smtp.office365.com";
    $mail->SMTPAuth = true;
    $mail->AuthType = "XOAUTH2";
    $mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
    $mail->SMTPSecure = "tls";
    $mail->Port = 587;


	$username = 'smtpas400@rgpballs.com';
	$clientId = '7982c594-1dbf-4143-9a44-38149cb100fb';
	$clientSecret = 'hnr8Q~j_24vK8tCzOozJfZaUJTRDscQbt.uS.arc';
	$tenantId = '32585919-7720-4442-abda-857d4c61805c';
	$refreshToken = '0.AUcAGVlYMiB3QkSr2oV9TGGAXJTFgnm_HUNBmkQ4FJyxAPsNAX0.AgABAAEAAADnfolhJpSnRYB1SVj-Hgd8AgDs_wUA9P-rO9xF_-FtGRo89S2nP9pLZ3-MK5aiSYDlOHbFgGx7Gwlc0z225VFhiFfdmCd4uc14SHT1rU8qDUZHY8JpiFwtToKVDFKdftS1Xysb_7V9CmYIvFUnN6FfmtflnMo2AANtzIFN8r8AozF987RrQyTJdgSCwqZ5lUIKYPHVH1QhBuL689yRPdJuQV_lYzRLQbPxRdD8tkUPqXIT8DjnBu1JOPJ5XNSGwtrQTgVI7UKpUFF6CPmxZuo5IpV-9rW7OKv_CZw65lm0I2XDfwzoAy0dZMaYRR_aco2TUOHKgU_FDsYHuJxSkvX5dD62biVfdrjHIF2B8yhTwQQU4T-fWco3Jq81jnT4OHbqCtlTK9SCoaHIUzlmFekPstq9hQKESQLGWjBI70g5dtJ_50ZBJwXHiX_ztOE68NSYxWcv610xxTsN9lGeW1OPUyb0tv7i0glfRQBtEmpCJ_GLAskZDhqOH0c_oyDOV79Vnpjlg_jQxQmOu6M0lFo7XoRTMMuGu-ArJueymJ9R2mkHoW4zrd9z2u1yAn0qUerTCvuKy4ECj8JohOQwOmp_C6ifMoP7qEUUlWvN_K7aMSH4nJ_48WnA_66Lwdrp5rk9ajdcYf74l6GPa-qW85vOfEwZTRZ0LlwIIx7IRHK5WreUBKFqqxiXhhCpJ3k10zVeRuPd8OkT1RcCF2-doiZNwJscW6_VvC-ZWEqvsgd_9twy';
	
    $provider->urlAPI = "https://outlook.office365.com/.default";
    $provider->scope = "Mail.Send";

    $mail->setOAuth(
        new OAuth(
            [
                "provider" => $provider,
                "clientId" => $clientId,
                "clientSecret" => $clientSecret,
                "refreshToken" => $refreshToken, 
                "userName" =>$username
            ]
        )
    );

    $mail->From = $username;
    $mail->AddAddress("mattia.marsura@bigblue.it");   
    $mail->IsHTML(true);
    $mail->Subject = "This is a test subject";
    $mail->Body = "Hello, how are you?";
    $mail->Send();
?>
