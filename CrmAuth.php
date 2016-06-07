<?php

include_once "CrmAuthenticationHeader.php";
class CrmAuth {
	
	/**
	 * Gets a CRM Online SOAP header & expiration.
	 * 
	 * @return CrmAuthenticationHeader An object containing the SOAP header and expiration date/time of the header.
	 * @param String $username
	 *        	Username of a valid CRM user.
	 * @param String $password
	 *        	Password of a valid CRM user.
	 * @param String $url
	 *        	The Url of the CRM Online organization (https://org.crm.dynamics.com).
	 */
	public function GetHeaderOnline($username, $password, $url) {
		$url .= (substr ( $url, - 1 ) == '/' ? '' : '/');
		$urnAddress = $this->GetUrnOnline ( $url );
		$now = $_SERVER ['REQUEST_TIME'];
		
		$xml = '
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://www.w3.org/2005/08/addressing" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
	<s:Header>
		<a:Action s:mustUnderstand="1">http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue</a:Action>
		<a:MessageID>urn:uuid:' . $this->getGUID () . '</a:MessageID>
		<a:ReplyTo>
			<a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
		</a:ReplyTo>
		<a:To s:mustUnderstand="1">https://login.microsoftonline.com/RST2.srf</a:To>
		<o:Security s:mustUnderstand="1" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			<u:Timestamp u:Id="_0">
				<u:Created>' . gmdate ( 'Y-m-d\TH:i:s.u\Z', $now ) . '</u:Created>
				<u:Expires>' . gmdate ( 'Y-m-d\TH:i:s.u\Z', strtotime ( '+60 minute', $now ) ) . '</u:Expires>
			</u:Timestamp>
			<o:UsernameToken u:Id="uuid-' . $this->getGUID () . '-1">
				<o:Username>' . $username . '</o:Username>
				<o:Password>' . $password . '</o:Password>
			</o:UsernameToken>
		</o:Security>
	</s:Header>
	<s:Body>
		<trust:RequestSecurityToken xmlns:trust="http://schemas.xmlsoap.org/ws/2005/02/trust">
			<wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
			<a:EndpointReference>
				<a:Address>urn:' . $urnAddress . '</a:Address>
			</a:EndpointReference>
			</wsp:AppliesTo>
			<trust:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue</trust:RequestType>
		</trust:RequestSecurityToken>
	</s:Body>
</s:Envelope>
		';
		
		$headers = array (
				"POST " . "/RST2.srf" . " HTTP/1.1",
				"Host: " . "login.microsoftonline.com",
				'Connection: Keep-Alive',
				"Content-type: application/soap+xml; charset=UTF-8",
				"Content-length: " . strlen ( $xml ) 
		);
		
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, "https://login.microsoftonline.com/RST2.srf" );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 60 );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt ( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $xml );
		
		$response = curl_exec ( $ch );
		curl_close ( $ch );
		
		$responsedom = new DomDocument ();
		$responsedom->loadXML ( $response );
		
		$cipherValues = $responsedom->getElementsbyTagName ( "CipherValue" );
		$token1 = $cipherValues->item ( 0 )->textContent;
		$token2 = $cipherValues->item ( 1 )->textContent;
		
		$keyIdentiferValues = $responsedom->getElementsbyTagName ( "KeyIdentifier" );
		$keyIdentifer = $keyIdentiferValues->item ( 0 )->textContent;
		
		$tokenExpiresValues = $responsedom->getElementsbyTagName ( "Expires" );
		$tokenExpires = $tokenExpiresValues->item ( 0 )->textContent;
		
		$authHeader = new CrmAuthenticationHeader ();
		$authHeader->Expires = $tokenExpires;
		$authHeader->ExecuteHeader = $this->CreateSoapHeaderOnline ( $url, $keyIdentifer, $token1, $token2, 'Execute' );
		$authHeader->CreateHeader = $this->CreateSoapHeaderOnline ( $url, $keyIdentifer, $token1, $token2, 'Create' );
		
		return $authHeader;
	}
	
	/**
	 * Gets a CRM Online SOAP header.
	 * 
	 * @return String The XML SOAP header to be used in future requests.
	 * @param String $url
	 *        	The Url of the CRM Online organization (https://org.crm.dynamics.com).
	 * @param String $keyIdentifer
	 *        	The KeyIdentifier from the initial request.
	 * @param String $token1
	 *        	The first token from the initial request.
	 * @param String $token2
	 *        	The second token from the initial request.
	 */
	function CreateSoapHeaderOnline($url, $keyIdentifer, $token1, $token2, $action) {
		$xml = '
<s:Header>
	<a:Action s:mustUnderstand="1">http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/'.$action.'</a:Action>
		<Security xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			<EncryptedData Id="Assertion0" Type="http://www.w3.org/2001/04/xmlenc#Element" xmlns="http://www.w3.org/2001/04/xmlenc#">
			<EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#tripledes-cbc"/>
			<ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
				<EncryptedKey>
					<EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p"/>
					<ds:KeyInfo Id="keyinfo">
						<wsse:SecurityTokenReference xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
							<wsse:KeyIdentifier EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509SubjectKeyIdentifier">' . $keyIdentifer . '</wsse:KeyIdentifier>
						</wsse:SecurityTokenReference>
					</ds:KeyInfo>
					<CipherData>
						<CipherValue>' . $token1 . '</CipherValue>
					</CipherData>
				</EncryptedKey>
			</ds:KeyInfo>
			<CipherData>
				<CipherValue>' . $token2 . '</CipherValue>
			</CipherData>
			</EncryptedData>
		</Security>
		<a:MessageID>urn:uuid:' . $this->getGUID () . '</a:MessageID>
		<a:ReplyTo>
			<a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
		</a:ReplyTo>
	<a:To s:mustUnderstand="1">' . $url . 'XRMServices/2011/Organization.svc</a:To>
</s:Header>
		';
		
		return $xml;
	}
	
	/**
	 * Gets the correct URN Address based on the Online region.
	 * 
	 * @return String URN Address.
	 * @param String $url
	 *        	The Url of the CRM Online organization (https://org.crm.dynamics.com).
	 */
	function GetUrnOnline($url) {
		if (strpos ( strtoupper ( $url ), "CRM2.DYNAMICS.COM" )) {
			return "crmsam:dynamics.com";
		}
		if (strpos ( strtoupper ( $url ), "CRM4.DYNAMICS.COM" )) {
			return "crmemea:dynamics.com";
		}
		if (strpos ( strtoupper ( $url ), "CRM5.DYNAMICS.COM" )) {
			return "crmapac:dynamics.com";
		}
		if (strpos ( strtoupper ( $url ), "CRM6.DYNAMICS.COM" )) {
			return "crmoce:dynamics.com";
		}
		if (strpos ( strtoupper ( $url ), "CRM7.DYNAMICS.COM" )) {
			return "crmjpn:dynamics.com";
		}
		if (strpos ( strtoupper ( $url ), "CRM9.DYNAMICS.COM" )) {
			return "crmgcc:dynamics.com";
		}
		
		return "crmna:dynamics.com";
	}
	
	/**
	 * Gets a CRM On Premise SOAP header & expiration.
	 * 
	 * @return CrmAuthenticationHeader An object containing the SOAP header and expiration date/time of the header.
	 * @param String $username
	 *        	Username of a valid CRM user.
	 * @param String $password
	 *        	Password of a valid CRM user.
	 * @param String $url
	 *        	The Url of the CRM On Premise (IFD) organization (https://org.domain.com).
	 */
	function GetHeaderOnPremise($username, $password, $url) {
		$url .= (substr ( $url, - 1 ) == '/' ? '' : '/');
		$adfsUrl = $this->GetADFS ( $url );
		$now = $_SERVER ['REQUEST_TIME'];
		$urnAddress = $url . "XRMServices/2011/Organization.svc";
		$usernamemixed = $adfsUrl . "/13/usernamemixed";
		
		$xml = '
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://www.w3.org/2005/08/addressing">
	<s:Header>
		<a:Action s:mustUnderstand="1">http://docs.oasis-open.org/ws-sx/ws-trust/200512/RST/Issue</a:Action>
		<a:MessageID>urn:uuid:' . $this->getGUID () . '</a:MessageID>
		<a:ReplyTo>
			<a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
		</a:ReplyTo>
		<Security s:mustUnderstand="1" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			<u:Timestamp  u:Id="" . $this->getGUID () . "">
				<u:Created>' . gmdate ( 'Y-m-d\TH:i:s.u\Z', $now ) . '</u:Created>
				<u:Expires>' . gmdate ( 'Y-m-d\TH:i:s.u\Z', strtotime ( '+60 minute', $now ) ) . '</u:Expires>
			</u:Timestamp>
			<UsernameToken u:Id="' . $this->getGUID () . '">
				<Username>' . $username . '</Username>
				<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $password . '</Password>
			</UsernameToken>
		</Security>
		<a:To s:mustUnderstand="1">' . $usernamemixed . '</a:To>
	</s:Header>
	<s:Body>
		<trust:RequestSecurityToken xmlns:trust="http://docs.oasis-open.org/ws-sx/ws-trust/200512">
			<wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
				<a:EndpointReference>
					<a:Address>' . $urnAddress . '</a:Address>
				</a:EndpointReference>
			</wsp:AppliesTo>
			<trust:RequestType>http://docs.oasis-open.org/ws-sx/ws-trust/200512/Issue</trust:RequestType>
		</trust:RequestSecurityToken>
	</s:Body>
</s:Envelope>
		';
		
		$headers = array (
				"POST " . parse_url ( $usernamemixed, PHP_URL_PATH ) . " HTTP/1.1",
				"Host: " . parse_url ( $adfsUrl, PHP_URL_HOST ),
				'Connection: Keep-Alive',
				"Content-type: application/soap+xml; charset=UTF-8",
				"Content-length: " . strlen ( $xml ) 
		);
		
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $usernamemixed );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 60 );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt ( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $xml );
		
		$response = curl_exec ( $ch );
		curl_close ( $ch );
		
		$responsedom = new DomDocument ();
		$responsedom->loadXML ( $response );
		
		$cipherValues = $responsedom->getElementsbyTagName ( "CipherValue" );
		$token1 = $cipherValues->item ( 0 )->textContent;
		$token2 = $cipherValues->item ( 1 )->textContent;
		
		$keyIdentiferValues = $responsedom->getElementsbyTagName ( "KeyIdentifier" );
		$keyIdentifer = $keyIdentiferValues->item ( 0 )->textContent;
		
		$x509IssuerNames = $responsedom->getElementsbyTagName ( "X509IssuerName" );
		$x509IssuerName = $x509IssuerNames->item ( 0 )->textContent;
		
		$x509SerialNumbers = $responsedom->getElementsbyTagName ( "X509SerialNumber" );
		$x509SerialNumber = $x509SerialNumbers->item ( 0 )->textContent;
		
		$binarySecrets = $responsedom->getElementsbyTagName ( "BinarySecret" );
		$binarySecret = $binarySecrets->item ( 0 )->textContent;
		
		$created = gmdate ( 'Y-m-d\TH:i:s.u\Z', strtotime ( '-1 minute', $now ) );
		$expires = gmdate ( 'Y-m-d\TH:i:s.u\Z', strtotime ( '+5 minute', $now ) );
		$timestamp = '
<u:Timestamp xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" u:Id="_0">
	<u:Created>' . $created . '</u:Created>
	<u:Expires>' . $expires . '</u:Expires>
</u:Timestamp>
		';
		
		$hashedDataBytes = sha1 ( $timestamp, true );
		$digestValue = base64_encode ( $hashedDataBytes );
		
		$signedInfo = '
<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
	<CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#">
	</CanonicalizationMethod>
	<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#hmac-sha1">
	</SignatureMethod>
	<Reference URI="#_0">
		<Transforms>
			<Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#">
			</Transform>
		</Transforms>
		<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">
		</DigestMethod>
		<DigestValue>' . $digestValue . '</DigestValue>
	</Reference>
</SignedInfo>
		';
		$binarySecretBytes = base64_decode ( $binarySecret );
		$hmacHash = hash_hmac ( "sha1", $signedInfo, $binarySecretBytes, true );
		$signatureValue = base64_encode ( $hmacHash );
		
		$tokenExpiresValues = $responsedom->getElementsbyTagName ( "Expires" );
		$tokenExpires = $tokenExpiresValues->item ( 0 )->textContent;
		
		$authHeader = new CrmAuthenticationHeader ();
		$authHeader->Expires = $tokenExpires;
		$authHeader->Header = $this->CreateSoapHeaderOnPremise ( $url, $keyIdentifer, $token1, $token2, $x509IssuerName, $x509SerialNumber, $signatureValue, $digestValue, $created, $expires );
		
		return $authHeader;
	}
	
	/**
	 * Gets a CRM On Premise (IFD) SOAP header.
	 * 
	 * @return String SOAP Header XML.
	 * @param String $url
	 *        	The Url of the CRM On Premise (IFD) organization (https://org.domain.com).
	 * @param String $keyIdentifer
	 *        	The KeyIdentifier from the initial request.
	 * @param String $token1
	 *        	The first token from the initial request.
	 * @param String $token2
	 *        	The second token from the initial request.
	 * @param String $x509IssuerName
	 *        	The certificate issuer.
	 * @param String $x509SerialNumber
	 *        	The certificate serial number.
	 * @param String $signatureValue
	 *        	The hashsed value of the header signature.
	 * @param String $digestValue
	 *        	The hashed value of the header timestamp.
	 * @param String $created
	 *        	The header created date/time.
	 * @param String $expires
	 *        	The header expiration date/tim.
	 */
	function CreateSoapHeaderOnPremise($url, $keyIdentifer, $token1, $token2, $x509IssuerName, $x509SerialNumber, $signatureValue, $digestValue, $created, $expires) {
		$xml = '
<s:Header>
	<a:Action s:mustUnderstand="1">http://schemas.microsoft.com/xrm/2011/Contracts/Services/IOrganizationService/Execute</a:Action>
	<a:MessageID>urn:uuid:' . $this->getGUID () . '</a:MessageID>
	<a:ReplyTo>
		<a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
	</a:ReplyTo>
	<a:To s:mustUnderstand="1">' . $url . 'XRMServices/2011/Organization.svc</a:To>
	<o:Security xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
		<u:Timestamp xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" u:Id="_0">
			<u:Created>' . $created . '</u:Created>
			<u:Expires>' . $expires . '</u:Expires>
		</u:Timestamp>
		<xenc:EncryptedData Type="http://www.w3.org/2001/04/xmlenc#Element" xmlns:xenc="http://www.w3.org/2001/04/xmlenc#">
			<xenc:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes256-cbc"/>
			<KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
				<e:EncryptedKey xmlns:e="http://www.w3.org/2001/04/xmlenc#">
					<e:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p">
						<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
					</e:EncryptionMethod>
					<KeyInfo>
						<o:SecurityTokenReference xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
							<X509Data>
								<X509IssuerSerial>
									<X509IssuerName>' . $x509IssuerName . '</X509IssuerName>
									<X509SerialNumber>' . $x509SerialNumber . '</X509SerialNumber>
								</X509IssuerSerial>
							</X509Data>
						</o:SecurityTokenReference>
					</KeyInfo>
					<e:CipherData>
						<e:CipherValue>' . $token1 . '</e:CipherValue>
					</e:CipherData>
				</e:EncryptedKey>
			</KeyInfo>
			<xenc:CipherData>
				<xenc:CipherValue>' . $token2 . '</xenc:CipherValue>
			</xenc:CipherData>
		</xenc:EncryptedData>
		<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
			<SignedInfo>
				<CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
				<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#hmac-sha1"/>
				<Reference URI="#_0">
					<Transforms>
						<Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
					</Transforms>
					<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
					<DigestValue>' . $digestValue . '</DigestValue>
				</Reference>
			</SignedInfo>
			<SignatureValue>' . $signatureValue . '</SignatureValue>
			<KeyInfo>
				<o:SecurityTokenReference xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
					<o:KeyIdentifier ValueType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.0#SAMLAssertionID">' . $keyIdentifer . '</o:KeyIdentifier>
				</o:SecurityTokenReference>
			</KeyInfo>
		</Signature>
	</o:Security>
</s:Header>
		';
		
		return $xml;
	}
	
	/**
	 * Gets the name of the AD FS server CRM uses for authentication.
	 * 
	 * @return String The AD FS server url.
	 * @param String $url
	 *        	The Url of the CRM On Premise (IFD) organization (https://org.domain.com).
	 */
	function GetADFS($url) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url . "XrmServices/2011/Organization.svc?wsdl=wsdl0" );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 60 );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
		
		$response = curl_exec ( $ch );
		curl_close ( $ch );
		
		$responsedom = new DomDocument ();
		$responsedom->loadXML ( $response );
		
		$identifiers = $responsedom->getElementsbyTagName ( "Identifier" );
		$identifier = $identifiers->item ( 0 )->textContent;
		
		return str_replace ( "http://", "https://", $identifier );
	}
	
	// http://stackoverflow.com/questions/18206851/com-create-guid-function-got-error-on-server-side-but-works-fine-in-local-usin
	function getGUID() {
		if (function_exists ( 'com_create_guid' )) {
			return com_create_guid ();
		} else {
			mt_srand ( ( double ) microtime () * 10000 ); // optional for php 4.2.0 and up.
			$charid = strtoupper ( md5 ( uniqid ( rand (), true ) ) );
			$hyphen = chr ( 45 ); // "-"
			$uuid = 
			    chr ( 123 ) . // "{"
				substr ( $charid, 0, 8 ) . $hyphen . substr ( $charid, 8, 4 ) . $hyphen . substr ( $charid, 12, 4 ) . $hyphen . substr ( $charid, 16, 4 ) . $hyphen . substr ( $charid, 20, 12 ) .
				chr ( 125 ); // "}"
			return $uuid;
		}
	}
}

