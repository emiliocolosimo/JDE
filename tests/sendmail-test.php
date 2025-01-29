<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'authsmtp.securemail.pro';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'xxxxx';                     //SMTP username
    $mail->Password   = 'xxxxx';                               //SMTP password
    //$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

	/*
	Id applicazione: 7982c594-1dbf-4143-9a44-38149cb100fb
	Id segreto: hnr8Q~j_24vK8tCzOozJfZaUJTRDscQbt.uS.arc
	Id tenant(directory): 32585919-7720-4442-abda-857d4c61805c
	*/
	
    //Recipients
    $mail->setFrom('mattia.marsura@bigblue.it', 'Mailer');
    $mail->addAddress('mattia.marsura@gmail.com', 'Mattia Marsura');     //Add a recipient
  
    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'Presentazione RGPBALLS';
    $mail->Body    = '
	<div style="font-size:14px">
	<p>
		<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="cid:image001" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="cid:image002" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<img width="270" height="270" style="width:2.8125in;height:2.8125in" src="cid:image003" >
	</p>
	<p>
		<img width="662" height="323" style="width:6.8958in;height:3.3645in" src="cid:image004" >
	</p>	

	<p style="margin-bottom:0cm;text-align:justify;line-height:normal">
	<span style="font-family:\'Arial,sans-serif\'">Buongiorno Sig. {w=p_usermeta.meta_key} valori first_name e last_name</span>
	</p>	 

	<p style="margin-bottom:0cm;text-align:justify;line-height:normal">
	<i><span style="font-family:\'Arial,sans-serif\'">Alla gentile attenzione del Vs Ufficio Acquisti</span></i>
	</p>
  
	<p style="margin-bottom:0cm;text-align:justify;line-height:normal">
	<span style="font-family:\'Arial,sans-serif\'">RGPBALLS Srl &egrave; da oltre cinquant\'anni tra le pi&ugrave; importanti aziende europee nella produzione, commercio e distribuzione di:</span>
	</p>

	<p style="margin-bottom:0cm;text-align:justify;line-height:normal">
	<i><span style="font-family:\'Arial,sans-serif\'">&nbsp;</span></i>
	</p>

	<ul style="margin-top:0cm" type="disc">
	<li style="margin-bottom:0cm;text-align:justify;line-height:normal;mso-list:l3 level1 lfo1">
	<span style="font-family:\'Arial,sans-serif\'">SFERE DI PRECISIONE </span>
	
	<span style="font-family:\'Arial,sans-serif\'"> -&gt; </span>
	<span style="font-family:\'Arial,sans-serif\'"> abbiamo produzioni in Cina, India e Italia, e possiamo indirizzare il cliente verso la soluzione pi&ugrave; consona alle sue esigenze e requisiti;</span>
	</li>
	</ul>

	
	<ul style="margin-top:0cm" type="disc">
	<li style="margin-bottom:0cm;text-align:justify;line-height:normal;mso-list:l3 level1 lfo1">
	<span style="font-family:\'Arial,sans-serif\'">SFERE <u>MADE IN ITALY</u> IN MISURE SPECIALI </span>
	
	<span style="font-family:\'Arial,sans-serif\'"> -&gt; </span>
	<span style="font-family:\'Arial,sans-serif\'"> possiamo fornire piccoli lotti di sfere ad alta precisione in diametri speciali, prodotte nel nostro stabilimento in Italia;</span>
	</li>
	</ul>

	
	<ul style="margin-top:0cm" type="disc">
	<li style="margin-bottom:0cm;text-align:justify;line-height:normal;mso-list:l3 level1 lfo1">
	<span style="font-family:\'Arial,sans-serif\'">RULLI DI PRECISIONE </span>
	
	<span style="font-family:\'Arial,sans-serif\'"> -&gt; </span>
	<span style="font-family:\'Arial,sans-serif\'"> abbiamo produzioni in Cina <u>e in Italia</u>;</span>
	</li>
	</ul>


	<ul style="margin-top:0cm" type="disc">
	<li style="margin-bottom:0cm;text-align:justify;line-height:normal;mso-list:l3 level1 lfo1">
	<span style="font-family:\'Arial,sans-serif\'">SFERE PORTANTI <u>MADE IN ITALY</u></span>
	
	<span style="font-family:\'Arial,sans-serif\'"> -&gt; </span>
	<span style="font-family:\'Arial,sans-serif\'"> questi articoli sono <u>prodotti esclusivamente in Italia</u>. La gamma comprende i modelli standard, adatti alla maggior parte delle applicazioni, e modelli custom realizzati appositamente in base alle esigenze del cliente.</span>
	</li>
	</ul>

	<p style="margin-bottom:0cm;text-align:justify;line-height:normal">
	<i><span style="font-family:\'Arial,sans-serif\'">&nbsp;</span></i>
	</p>
	 
	<p style="margin-bottom:0cm;text-align:justify;line-height:normal">
	<span style="font-family:\'Arial,sans-serif\'">Siamo in grado di offrire un servizio professionale, qualit&agrave; affidabile e prezzi competitivi, rimaniamo a disposizione per eventuali richieste di offerta in merito.</span><span style=3D"font-size:11.0pt"><o:p></o:p></span></p>


	<p>
	<span style="font-family:\'Arial,sans-serif\'">Potete trovare i nostri video istituzionali al seguente link: </span><a href="http://www.youtube.com/@rgpballs"><span style="font-family:\'Arial,sans-serif\'">www.youtube.com/@rgpballs</span></a>
	</p>
	
	<p>
	<span style="font-family:\'Arial,sans-serif\'">
	Vi invitiamo a contattarci senza impegno, con l\'auspicio di intrattenere una proficua collaborazione.	
	</span>
	</p>	
	
	<p>
	<span style="font-family:\'Arial,sans-serif\'">
	Cordiali saluti
	</span>
	</p>
	</div>
	
	';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

	$mail->AddEmbeddedImage('img/image1.jpg', 'image001'); 
	$mail->AddEmbeddedImage('img/image2.jpg', 'image002'); 
	$mail->AddEmbeddedImage('img/image3.jpg', 'image003'); 
	$mail->AddEmbeddedImage('img/image4.png', 'image004'); 

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}