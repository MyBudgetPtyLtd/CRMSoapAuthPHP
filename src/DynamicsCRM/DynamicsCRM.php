<?php

namespace DynamicsCRM;

use DateTime;
use DOMDocument;
use DynamicsCRM\Authorization\CrmAuth;
use DynamicsCRM\Authorization\CrmOnlineAuth;
use DynamicsCRM\Authorization\CrmOnPremisesAuth;
use DynamicsCRM\Authorization\CrmUser;
use DynamicsCRM\Http\SoapRequester;
use DynamicsCRM\Integration\AuthorizationCache;
use DynamicsCRM\Integration\AuthorizationSettingsProvider;
use DynamicsCRM\Integration\SingleRequestAuthorizationCache;
use DynamicsCRM\Requests\Request;
use DynamicsCRM\Requests\RetrieveUserRequest;
use DynamicsCRM\Requests\WhoAmIRequest;
use DynamicsCRM\Response\RetrieveUserResponse;
use DynamicsCRM\Response\WhoAmIResponse;
use Psr\Log\LoggerInterface;

class DynamicsCRM
{
    public function __construct(AuthorizationSettingsProvider $authorizationSettingsProvider, AuthorizationCache $authorizationCache, LoggerInterface $logger) {
        $this->AuthorizationSettingsProvider = $authorizationSettingsProvider;
        $this->AuthorizationCache = $authorizationCache;
        $this->Logger = $logger;
        $this->SoapRequestor = new SoapRequester($logger);
    }

    public function Request(Request $request) {
        $token = $this->GetAuthorizationToken();
        
        $xml = "<s:Envelope xmlns:s=\"http://www.w3.org/2003/05/soap-envelope\">";
        $xml .= $token->CreateSoapHeader($request->getAction());
        $xml .= $request->getRequestXML();
        $xml .= "</s:Envelope>";

        $response = $this->SoapRequestor->sendRequest($token->Url."XRMServices/2011/Organization.svc", $xml);
        $responseDOM = new DOMDocument();
        $responseDOM->loadXML($response);
        return $request->createResponse($responseDOM);
    }

    public function GetCurrentUserId() {
        //Verify auth is current.
        $this->GetAuthorizationToken();
        return $this->AuthorizationCache->getUserIdentity()->getUserId();
    }

    private function GetAuthorizationToken()
    {
        $token = $this->AuthorizationCache->getAuthorizationToken();
        $now = $_SERVER['REQUEST_TIME'];

        if ($token == null || (new DateTime($token->Expires))->getTimestamp() < $now ) {
            $crmAuth = $this->CreateCrmAuth(
                $this->AuthorizationSettingsProvider->getCRMUri(),
                $this->AuthorizationSettingsProvider->getUsername(),
                $this->AuthorizationSettingsProvider->getPassword()
            );
            $token = $crmAuth->Authenticate();

            //Hits up CRM with a WhoAmI to get the user's ID and name
            $tempCache = new SingleRequestAuthorizationCache();
            $tempCache->storeAuthorizationToken($token);
            $subRequest = new self($this->AuthorizationSettingsProvider, $tempCache, $this->Logger);
            /** @var WhoAmIResponse $whoAmIResponse */
            $whoAmIResponse = ($subRequest->Request(new WhoAmIRequest()));
            /** @var RetrieveUserResponse $userResponse */
            $userResponse = ($subRequest->Request(new RetrieveUserRequest($whoAmIResponse->getUserId())));

            $user = new CrmUser($whoAmIResponse->getUserId(), $userResponse->getFirstName(), $userResponse->getLastName());

            $this->AuthorizationCache->storeUserIdentity($user);
            //Store the authorization token and identity.
            $this->AuthorizationCache->storeAuthorizationToken($token);
        }
        return $token;
    }

    /**
     * Creates a CrmAuth appropriate for the URL given.
     * @return CrmAuth a CrmAuth you can use to authenticate with.
     * @param String $username
     *        	Username of a valid CRM user.
     * @param String $password
     *        	Password of a valid CRM user.
     * @param String $url
     *        	The Url of the CRM Online organization (https://org.crm.dynamics.com).
     */
    //TODO: Factory Class
    public function CreateCrmAuth($url, $username, $password) {
        if (strpos ( strtoupper ( $url ), ".DYNAMICS.COM" )) {
            return new CrmOnlineAuth($url, $username, $password, $this->SoapRequestor, $this->Logger);
        } else {
            return new CrmOnPremisesAuth($url, $username, $password, $this->SoapRequestor, $this->Logger);
        }
    }


}