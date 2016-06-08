<?php
namespace DynamicsCRM;

use DynamicsCRM\Auth\CrmAuth;
use DynamicsCRM\Integration\AuthorizationCache;
use DynamicsCRM\Integration\AuthorizationSettingsProvider;
use DynamicsCRM\Requests\Request;


class CrmExecuteSoap {

    function __construct(AuthorizationSettingsProvider $authorizationSettingsProvider, AuthorizationCache $authorizationCache) {
        $this->AuthorizationSettingsProvider = $authorizationSettingsProvider;
        $this->AuthorizationCache = $authorizationCache;
    }

    /**
     * Executes the SOAP request.
     * @return String SOAP response.
     * @param Request $request
     *        	The SOAP request body.
     */
    public function ExecuteCRMRequest(Request $request) {

        $token = $this->AuthorizationCache->getAuthorizationToken();
        if ($token == null || $token->Expires < date() ) {
            $crmAuth = CrmAuth::Create(
                $this->AuthorizationSettingsProvider->getCRMUri(),
                $this->AuthorizationSettingsProvider->getUsername(),
                $this->AuthorizationSettingsProvider->getPassword()
            );
            $token = $crmAuth->Authenticate();
            $this->AuthorizationCache->storeAuthorizationToken($token);
        }

        $url = rtrim ( $token->Url, "/" );
        //xmlns:a="http://www.w3.org/2005/08/addressing"
        $xml = "<s:Envelope xmlns:s=\"http://www.w3.org/2003/05/soap-envelope\">";
        $xml .= $token->CreateSoapHeader($request->getAction());
        $xml .= $request->getRequestXML();
        $xml .= "</s:Envelope>";

        $headers = array (
            "POST " . "/Organization.svc" . " HTTP/1.1",
            "Host: " . str_replace ( "https://", "", $url ),
            'Connection: Keep-Alive',
            "Content-type: application/soap+xml; charset=UTF-8",
            "Content-length: " . strlen ( $xml )
        );

        $cURL = curl_init ();
        curl_setopt ( $cURL, CURLOPT_URL, $url . "/XRMServices/2011/Organization.svc" );
        curl_setopt ( $cURL, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $cURL, CURLOPT_TIMEOUT, 60 );
        curl_setopt ( $cURL, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt ( $cURL, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
        curl_setopt ( $cURL, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $cURL, CURLOPT_POST, 1 );
        curl_setopt ( $cURL, CURLOPT_POSTFIELDS, $xml );
        $response = curl_exec ( $cURL );
        curl_close ( $cURL );

        return $response;
    }
}
