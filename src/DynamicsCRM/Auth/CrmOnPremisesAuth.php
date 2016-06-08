<?php
namespace DynamicsCRM\Auth;

use DynamicsCRM\Auth\Token\AuthenticationToken;
use DynamicsCRM\Auth\Token\OnPremisesAuthenticationToken;
use DynamicsCRM\Guid;

class CrmOnPremisesAuth extends CrmAuth
{
    /**
     * Gets a CRM On Premise SOAP header & expiration.
     *
     * @return AuthenticationToken An object containing the SOAP header and expiration date/time of the header.
     */
    function Authenticate() {

        $url = $this->url.(substr ( $this->url, - 1 ) == '/' ? '' : '/');
        $adfsUrl = $this->GetADFS ( $url );
        $now = $_SERVER ['REQUEST_TIME'];
        $urnAddress = $url . "XRMServices/2011/Organization.svc";
        $usernamemixed = $adfsUrl . "/13/usernamemixed";

        $this->logger("Authenticating with $adfsUrl for".$url);

        $xml = '
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://www.w3.org/2005/08/addressing">
	<s:Header>
		<a:Action s:mustUnderstand="1">http://docs.oasis-open.org/ws-sx/ws-trust/200512/RST/Issue</a:Action>
		<a:MessageID>urn:uuid:' . Guid::newGuid() . '</a:MessageID>
		<a:ReplyTo>
			<a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
		</a:ReplyTo>
		<Security s:mustUnderstand="1" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			<u:Timestamp  u:Id="' . Guid::newGuid() . '">
				<u:Created>' . gmdate ( 'Y-m-d\TH:i:s.u\Z', $now ) . '</u:Created>
				<u:Expires>' . gmdate ( 'Y-m-d\TH:i:s.u\Z', strtotime ( '+60 minute', $now ) ) . '</u:Expires>
			</u:Timestamp>
			<UsernameToken u:Id="' . Guid::newGuid() . '">
				<Username>' . $this->username . '</Username>
				<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $this->password . '</Password>
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
        
        $response = $this->soapRequester->sendRequest($usernamemixed, $xml);

        $responseDom = new \DomDocument ();
        $responseDom->loadXML ( $response );

        $cipherValues = $responseDom->getElementsByTagName ( "CipherValue" );
        $token1 = $cipherValues->item ( 0 )->textContent;
        $token2 = $cipherValues->item ( 1 )->textContent;

        $keyIdentifierValues = $responseDom->getElementsByTagName ( "KeyIdentifier" );
        $keyIdentifier = $keyIdentifierValues->item ( 0 )->textContent;

        $x509IssuerNames = $responseDom->getElementsByTagName ( "X509IssuerName" );
        $x509IssuerName = $x509IssuerNames->item ( 0 )->textContent;

        $x509SerialNumbers = $responseDom->getElementsByTagName ( "X509SerialNumber" );
        $x509SerialNumber = $x509SerialNumbers->item ( 0 )->textContent;

        $binarySecrets = $responseDom->getElementsByTagName ( "BinarySecret" );
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

        $tokenExpiresValues = $responseDom->getElementsByTagName ( "Expires" );
        $tokenExpires = $tokenExpiresValues->item ( 0 )->textContent;

        $authHeader = new OnPremisesAuthenticationToken();
        $authHeader->Expires = $tokenExpires;
        $authHeader->Url = $url;
        $authHeader->KeyIdentifier = $keyIdentifier;
        $authHeader->Token1 = $token1;
        $authHeader->Token2 = $token2;
        $authHeader->X509IssuerName = $x509IssuerName;
        $authHeader->X509SerialNumber = $x509SerialNumber;
        $authHeader->SignatureValue = $signatureValue;
        $authHeader->DigestValue = $digestValue;
        $authHeader->Created = $created;
        $authHeader->Expires = $expires;

        return $authHeader;
    }

    /**
     * Gets the name of the AD FS server CRM uses for authentication.
     *
     * @return String The AD FS server url.
     * @param String $url
     *        	The Url of the CRM On Premise (IFD) organization (https://org.domain.com).
     */
    private function GetADFS($url) {
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url . "XrmServices/2011/Organization.svc?wsdl=wsdl0" );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 60 );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );

        $response = curl_exec ( $ch );
        curl_close ( $ch );

        $responseDom = new \DomDocument ();
        $responseDom->loadXML ( $response );

        $identifiers = $responseDom->getElementsByTagName ( "Identifier" );
        $identifier = $identifiers->item ( 0 )->textContent;

        return str_replace ( "http://", "https://", $identifier );
    }
}