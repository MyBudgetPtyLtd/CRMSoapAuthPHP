<?php
namespace DynamicsCRM\Auth;


use DynamicsCRM\Auth\Token\AuthenticationToken;
use DynamicsCRM\Auth\Token\OnlineAuthenticationToken;
use DynamicsCRM\Guid;

class CrmOnlineAuth extends CrmAuth
{
    /**
     * Gets a CRM Online SOAP header & expiration.
     *
     * @return AuthenticationToken An object containing the SOAP header and expiration date/time of the header.
     */
    public function Authenticate() {
        $url = $this->url.(substr ( $this->url, - 1 ) == '/' ? '' : '/');
        $urnAddress = $this->GetUrnOnline ( $url );
        $now = $_SERVER['REQUEST_TIME'];

        $this->logger->info("Authenticating with https://login.microsoftonline.com/RST2.srf for ".$url);

        $xml = '
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope" xmlns:a="http://www.w3.org/2005/08/addressing" xmlns:u="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
	<s:Header>
		<a:Action s:mustUnderstand="1">http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue</a:Action>
		<a:MessageID>urn:uuid:' . Guid::newGuid() . '</a:MessageID>
		<a:ReplyTo>
			<a:Address>http://www.w3.org/2005/08/addressing/anonymous</a:Address>
		</a:ReplyTo>
		<a:To s:mustUnderstand="1">https://login.microsoftonline.com/RST2.srf</a:To>
		<o:Security s:mustUnderstand="1" xmlns:o="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			<u:Timestamp u:Id="_0">
				<u:Created>' . gmdate ( 'Y-m-d\TH:i:s.u\Z', $now ) . '</u:Created>
				<u:Expires>' . gmdate ( 'Y-m-d\TH:i:s.u\Z', strtotime ( '+60 minute', $now ) ) . '</u:Expires>
			</u:Timestamp>
			<o:UsernameToken u:Id="uuid-' . Guid::newGuid() . '-1">
				<o:Username>' . $this->username . '</o:Username>
				<o:Password>' . $this->password . '</o:Password>
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

        $response = $this->soapRequester->sendRequest("https://login.microsoftonline.com/RST2.srf", $xml);

        $responseDom = new \DomDocument ();
        $responseDom->loadXML ( $response );

        $cipherValues = $responseDom->getElementsByTagName ( "CipherValue" );
        $token1 = $cipherValues->item ( 0 )->textContent;
        $token2 = $cipherValues->item ( 1 )->textContent;

        $keyIdentifierValues = $responseDom->getElementsByTagName ( "KeyIdentifier" );
        $keyIdentifier = $keyIdentifierValues->item ( 0 )->textContent;

        $tokenExpiresValues = $responseDom->getElementsByTagName ( "Expires" );
        $tokenExpires = $tokenExpiresValues->item ( 0 )->textContent;

        $authHeader = new OnlineAuthenticationToken();
        $authHeader->Expires = $tokenExpires;
        $authHeader->Url = $url;
        $authHeader->KeyIdentifier = $keyIdentifier;
        $authHeader->Token1 = $token1;
        $authHeader->Token2 = $token2;

        return $authHeader;
    }

    /**
     * Gets the correct URN Address based on the Online region.
     *
     * @return String URN Address.
     * @param String $url
     *        	The Url of the CRM Online organization (https://org.crm.dynamics.com).
     */
    private function GetUrnOnline($url) {
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
}