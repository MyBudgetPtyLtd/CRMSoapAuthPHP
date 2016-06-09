<?php
namespace DynamicsCRM\Authentication;

use DynamicsCRM\Authentication\Token\AuthenticationToken;
use DynamicsCRM\Authentication\Token\OnPremisesAuthenticationToken;

class OnPremisesAuthentication extends Authenticator
{
    /**
     * Gets a CRM On Premise SOAP header & expiration.
     *
     * @return AuthenticationToken An object containing the SOAP header and expiration date/time of the header.
     */
    function Authenticate() {

        $url = $this->url;
        $adfsUrl = $this->GetADFS ( $url );
        $now = $_SERVER ['REQUEST_TIME'];

        $usernamemixed = $adfsUrl . "/13/usernamemixed";

        $this->logger("Authenticating with $adfsUrl for".$url);
        $xml = $this->getAuthenticationRequest();

        $responseDom = $this->soapRequester->sendRequest($usernamemixed, $xml);

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

    protected function getUrnAddress()
    {
        return $this->url."XRMServices/2011/Organization.svc";
    }
}