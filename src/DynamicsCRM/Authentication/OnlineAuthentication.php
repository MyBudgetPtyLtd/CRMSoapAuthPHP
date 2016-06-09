<?php
namespace DynamicsCRM\Authentication;

use DOMDocument;
use DynamicsCRM\Authentication\Token\AuthenticationToken;
use DynamicsCRM\Authentication\Token\OnlineAuthenticationToken;
use DynamicsCRM\Guid;

class OnlineAuthentication extends Authenticator
{
    /**
     * Gets a CRM Online SOAP header & expiration.
     *
     * @return AuthenticationToken An object containing the SOAP header and expiration date/time of the header.
     */
    public function Authenticate() {
        $this->logger->info("Authenticating with https://login.microsoftonline.com/RST2.srf for ".$this->url);

        $xml = $this->getAuthenticationRequest();

        $responseDom = $this->soapRequester->sendRequest("https://login.microsoftonline.com/RST2.srf", $xml);

        $cipherValues = $responseDom->getElementsByTagName( "CipherValue" );
        $token1 = $cipherValues->item ( 0 )->textContent;
        $token2 = $cipherValues->item ( 1 )->textContent;

        $keyIdentifierValues = $responseDom->getElementsByTagName( "KeyIdentifier" );
        $keyIdentifier = $keyIdentifierValues->item ( 0 )->textContent;

        $tokenExpiresValues = $responseDom->getElementsByTagName( "Expires" );
        $tokenExpires = $tokenExpiresValues->item ( 0 )->textContent;

        $authHeader = new OnlineAuthenticationToken();
        $authHeader->Expires = $tokenExpires;
        $authHeader->Url = $this->url;
        $authHeader->KeyIdentifier = $keyIdentifier;
        $authHeader->Token1 = $token1;
        $authHeader->Token2 = $token2;

        return $authHeader;
    }

    /**
     * Gets the correct URN Address based on the Online region.
     *
     * @return String URN Address.
     */
    protected function getUrnAddress() {

        $url = $this->url;
        $urn = "urn:";

        if (strpos ( strtoupper ( $url ), "CRM2.DYNAMICS.COM" )) {
            $urn .= "crmsam:dynamics.com";
        } else if (strpos ( strtoupper ( $url ), "CRM4.DYNAMICS.COM" )) {
            $urn .= "crmemea:dynamics.com";
        } else if (strpos ( strtoupper ( $url ), "CRM5.DYNAMICS.COM" )) {
            $urn .= "crmapac:dynamics.com";
        } else if (strpos ( strtoupper ( $url ), "CRM6.DYNAMICS.COM" )) {
            $urn .= "crmoce:dynamics.com";
        } else if (strpos ( strtoupper ( $url ), "CRM7.DYNAMICS.COM" )) {
            $urn .= "crmjpn:dynamics.com";
        } else if (strpos ( strtoupper ( $url ), "CRM9.DYNAMICS.COM" )) {
            $urn .= "crmgcc:dynamics.com";
        } else {
            $urn .= "crmna:dynamics.com";
        }

        return $urn;
    }
}