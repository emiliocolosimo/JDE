<?php
 
	require_once("utils.inc.php");
 
	$urlOk = $_REQUEST['urlR'];

	$orien = $_REQUEST['orien'];  //P-L

	$dim = $_REQUEST['dim'];

	$savf = $_REQUEST['savf'];
	
	$nmfileout = $_REQUEST['nmfileout'];

	$port	 = $_REQUEST['port'];
	
	$headers = 0 ;
	$time_out = 0 ;

	$urlOk= str_replace('$','&',$urlOk);
	$urlOk= str_replace('(','?',$urlOk);

	
	$url =  'http://localhost'.(($port!='')?(':'.$port):('')).'/'.$urlOk;
 


	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, trim($url));
	curl_setopt($ch, CURLOPT_HEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, $time_out);
	$pdfhtml = curl_exec($ch);
	curl_close($ch);
 
	
	if($nmfileout=="") $nmfileout="documento";

	
	between_replace ("<!--", "-->", $pdfhtml, "");

	$content=$pdfhtml;
	$content=trim($content);

	 

	header('Content-type: application/pdf');	
	// convert in PDF
	require_once('html2pdf.class.php');
	try
	{

		$html2pdf = new HTML2PDF($orien, $dim , 'fr');
		//Per debug risorse, decommentare la riga successiva e aprire il file risultante come html:
		//$html2pdf->setModeDebug();
		$html2pdf->setTestTdInOnePage(false);
		$html2pdf->setDefaultFont('Arial');
		
		$html2pdf->writeHTML($content);

	 
		$html2pdf->Output($nmfileout.'.pdf');
		
	    if($savf=="S"){
            $html2pdf->Output($nmfileout.'.pdf','F');
	    }
	}
	catch(HTML2PDF_exception $e) {
		echo $e;
		exit;
	}


?>
