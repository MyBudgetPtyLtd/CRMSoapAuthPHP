<?php

namespace DynamicsCRM;

use DateTime;
use DOMDocument;
use DynamicsCRM\Authentication\Authenticator;
use DynamicsCRM\Authentication\OnlineAuthentication;
use DynamicsCRM\Authentication\OnPremisesAuthentication;
use DynamicsCRM\Authentication\CrmUser;
use DynamicsCRM\Authentication\Token\AuthenticationToken;
use DynamicsCRM\Http\SoapRequester;
use DynamicsCRM\Integration\AuthenticationCache;
use DynamicsCRM\Integration\DynamicsCRMSettingsProvider;
use DynamicsCRM\Integration\SingleRequestAuthenticationCache;
use DynamicsCRM\Requests\Request;
use DynamicsCRM\Requests\RetrieveUserRequest;
use DynamicsCRM\Requests\WhoAmIRequest;
use DynamicsCRM\Response\RetrieveUserResponse;
use DynamicsCRM\Response\WhoAmIResponse;
use Psr\Log\LoggerInterface;
use Twig_Environment;
use Twig_Loader_Filesystem;


class DynamicsCRM
{
    public function __construct(DynamicsCRMSettingsProvider $authenticationSettingsProvider, AuthenticationCache $authenticationCache, LoggerInterface $logger) {
        $this->AuthenticationSettingsProvider = $authenticationSettingsProvider;
        $this->AuthenticationCache = $authenticationCache;
        $this->Logger = $logger;
        $this->SoapRequestor = new SoapRequester($logger);

        $loader = new Twig_Loader_Filesystem();
        $loader->addPath(__dir__.'/Requests/Template', 'Request');
        $loader->addPath(__dir__.'/Authentication/Template', 'Authentication');
        $this->twig = new Twig_Environment($loader, array("debug"=>true));
    }

    public function Request(Request $request) {
        $token = $this->GetAuthenticationToken();
        
        $xml = "<s:Envelope xmlns:s=\"http://www.w3.org/2003/05/soap-envelope\">";
        //$xml .= $token->CreateSoapHeader($request->getAction());
        $xml .= $this->getHeaderForRequest($request, $token);

        $xml .= $this->getBodyForRequest($request);

        //$xml .= $request->getRequestXML();
        $xml .= "</s:Envelope>";

        $responseDOM = $this->SoapRequestor->sendRequest($token->Url."XRMServices/2011/Organization.svc", $xml);
        $response = $request->createResponse($responseDOM);
        return $response;
    }

    public function GetCurrentUserId() {
        //Verify auth is current.
        $this->GetAuthenticationToken();
        $crmUser = $this->AuthenticationCache->getUserIdentity();
        if ($crmUser) {
            return $crmUser->getUserId();
        } else {
            return Guid::zero();
        }
    }

    private function GetAuthenticationToken()
    {
        $token = $this->AuthenticationCache->getAuthenticationToken();
        $now = $_SERVER['REQUEST_TIME'];

        if ($token == null || (new DateTime($token->Expires))->getTimestamp() < $now ) {
            $crmAuth = $this->CreateCrmAuth(
                $this->AuthenticationSettingsProvider->getCRMUri(),
                $this->AuthenticationSettingsProvider->getUsername(),
                $this->AuthenticationSettingsProvider->getPassword()
            );
            $token = $crmAuth->Authenticate();

            //Hits up CRM with a WhoAmI to get the user's ID and name
            $tempCache = new SingleRequestAuthenticationCache();
            $tempCache->storeAuthenticationToken($token);
            $subRequest = new self($this->AuthenticationSettingsProvider, $tempCache, $this->Logger);
            /** @var WhoAmIResponse $whoAmIResponse */
            $whoAmIResponse = ($subRequest->Request(new WhoAmIRequest()));
            var_dump($whoAmIResponse);
            /** @var RetrieveUserResponse $userResponse */
            $request = new RetrieveUserRequest($whoAmIResponse->getUserId());
            var_dump($request);
            $userResponse = ($subRequest->Request($request));

            $user = new CrmUser($whoAmIResponse->getUserId(), $userResponse->getFirstName(), $userResponse->getLastName());
            var_export($user);
            $this->AuthenticationCache->storeUserIdentity($user);
            //Store the authentication token and identity.
            $this->AuthenticationCache->storeAuthenticationToken($token);
        }
        return $token;
    }

    /**
     * Creates a CrmAuth appropriate for the URL given.
     * @return Authenticator a CrmAuth you can use to authenticate with.
     * @param String $username
     *        	Username of a valid CRM user.
     * @param String $password
     *        	Password of a valid CRM user.
     * @param String $url
     *        	The Url of the CRM Online organization (https://org.crm.dynamics.com).
     */
    private function CreateCrmAuth($url, $username, $password) {
        if (strpos ( strtoupper ( $url ), ".DYNAMICS.COM" )) {
            return new OnlineAuthentication($url, $username, $password, $this->SoapRequestor, $this->twig, $this->Logger);
        } else {
            return new OnPremisesAuthentication($url, $username, $password, $this->SoapRequestor, $this->twig, $this->Logger);
        }
    }

    private function getHeaderForRequest(Request $request, AuthenticationToken $token) {
        $templateContext = [
            "action" => $request->getAction(),
            "token" => $token
        ];
        $tokenTemplateFilename = join('', array_slice(explode('\\', get_class($token)), -1));
        $template = $this->twig->loadTemplate("@Authentication/$tokenTemplateFilename.xml");
        return $template->render($templateContext);
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getBodyForRequest(Request $request)
    {
        $templateContext = [
            "userId" => $this->GetCurrentUserId(),
            "request" => $request
        ];
        $template = $this->twig->loadTemplate("@Request/".$request->getRequestTemplateFilename().".xml");
        return $template->render($templateContext);
    }


}