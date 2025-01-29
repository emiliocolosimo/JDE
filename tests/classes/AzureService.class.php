<?php
 
class AzureService {
	private $accessToken = null;
	private $lastError = '';
	private $azureTenantId = '';
	private $azureClientId = '';
	private $azureClientSecret = 'c';
	
    public function __construct()
    {
		
    }	
	
	public function setTenantId($azureTenantId) {
		$this->azureTenantId = $azureTenantId;
	}

	public function setClientId($azureClientId) {
		$this->azureClientId = $azureClientId;
	}
	
	public function setClientSecret($azureClientSecret) {
		$this->azureClientSecret = $azureClientSecret;
	}	
	
	public function retrieveAccessToken() { 
		$tokenUrl = 'https://login.microsoftonline.com/'.$this->$azureTenantId.'/oauth2/v2.0/token';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$tokenUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "client_id=".$this->$azureClientId."&scope=https://graph.microsoft.com/.default&client_secret=".$this->$azureClientSecret."&grant_type=client_credentials");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$server_output = curl_exec($ch);
		curl_close($ch);

		$arrResponse = json_decode($server_output);
		$token = $arrResponse->access_token;
		if($token=='') {
			$errorMessage = "Errore recupero access token:".$server_output;
			$this->setLastError($errorMessage);
			return false;
		}
		
		$this->accessToken = $token;
		return true;
	}
	
	public function sendMail($from,$to,$subject,$body) {
		$mailServiceUrl = 'https://graph.microsoft.com/v1.0/users/'.$from.'/sendMail';
		$ch = curl_init($mailServiceUrl);
		$payload = '{
		  "message": {
			"subject": "'.$subject.'",
			"body": {
			  "contentType": "HTML",
			  "content": '.json_encode($body).'
			},
			"toRecipients": [
			  {
				"emailAddress": {
				  "address": "'.$to.'"
				}
			  }
			] 	 
		  },
		  "saveToSentItems": "false"
		}';
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization: Bearer '.$this->accessToken));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($ch, CURLOPT_HEADER, true);

		$result = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpcode!=202) {
			$errorMessage = "Errore invio mail:responseCode:".$httpcode.":responseBody:".$result;
			$this->setLastError($errorMessage);
			return false;
		} else {
			return true;
		}
		curl_close($ch);
	}
	
	private function setLastError($errorMessage) {
		$this->lastError = $errorMessage; 
	}

	public function getLastError() {
		return $this->lastError; 
	}
}