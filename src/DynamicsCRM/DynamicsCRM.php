<?php

namespace DynamicsCRM;

use DateTime;
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
use Exception;
use Psr\Log\LoggerInterface;
use Twig_Environment;
use Twig_Loader_Filesystem;


class DynamicsCRM
{
    private $authenticationSettingsProvider;
    private $authenticationCache;
    private $logger;
    private $soapRequester;
    private $twig;

    public function __construct(DynamicsCRMSettingsProvider $authenticationSettingsProvider, AuthenticationCache $authenticationCache, LoggerInterface $logger, $additionalRequestTemplateDirs = null) {
        $this->authenticationSettingsProvider = $authenticationSettingsProvider;
        $this->authenticationCache = $authenticationCache;
        $this->logger = $logger;
        $this->soapRequester = new SoapRequester($logger);

        $loader = new Twig_Loader_Filesystem();
        $loader->addPath(__dir__.'/Requests/Template', 'Request');
        $loader->addPath(__dir__.'/Authentication/Template', 'Authentication');

        if (!empty($additionalRequestTemplateDirs)) {
            foreach ($additionalRequestTemplateDirs as $dir) {
                $loader->addPath($dir, 'Request');
            }
        }

        $this->twig = new Twig_Environment($loader, array());
    }

    public function Request(Request $request) {
        $token = $this->GetAuthenticationToken();
        
        $xml = "<s:Envelope xmlns:s=\"http://www.w3.org/2003/05/soap-envelope\">";
        $xml .= $this->getHeaderForRequest($request, $token);
        $xml .= $this->getBodyForRequest($request);
        $xml .= "</s:Envelope>";

        $responseDOM = $this->soapRequester->sendRequest($token->Url."XRMServices/2011/Organization.svc", $xml);
        $response = $request->createResponse($responseDOM);
        return $response;
    }

    public function GetCurrentUserId() {
        //Verify auth is current.
        $this->GetAuthenticationToken();
        $crmUser = $this->authenticationCache->getUserIdentity();
        if ($crmUser) {
            return $crmUser->getUserId();
        } else {
            return Guid::zero();
        }
    }

    private function GetAuthenticationToken()
    {
        $token = $this->authenticationCache->getAuthenticationToken();
        $now = $_SERVER['REQUEST_TIME'];

        if ($token == null || (new DateTime($token->Expires))->getTimestamp() < $now ) {
            $token = $this->renewAuthToken();

        }
        return $token;
    }

    public function renewAuthToken() {
	    $uri = $this->authenticationSettingsProvider->getCRMUri();
	    $username = $this->authenticationSettingsProvider->getUsername();
	    $password = $this->authenticationSettingsProvider->getPassword();

	    if (empty($uri)) {
		    throw new Exception("CRM URI has not been configured");
	    }
	    if (empty($username)) {
		    throw new Exception("CRM Username has not been configured");
	    }
	    if (empty($password)) {
		    throw new Exception("CRM Password has not been configured");
	    }


	    $crmAuth = $this->CreateCrmAuth($uri, $username, $password);
	    $token = $crmAuth->Authenticate();

	    //Hits up CRM with a WhoAmI to get the user's ID and name
	    $tempCache = new SingleRequestAuthenticationCache();
	    $tempCache->storeAuthenticationToken($token);
	    $subRequest = new self($this->authenticationSettingsProvider, $tempCache, $this->logger);
	    /** @var WhoAmIResponse $whoAmIResponse */
	    $whoAmIResponse = ($subRequest->Request(new WhoAmIRequest()));
	    /** @var RetrieveUserResponse $userResponse */
	    $request = new RetrieveUserRequest($whoAmIResponse->getUserId());
	    $userResponse = ($subRequest->Request($request));

	    $user = new CrmUser($whoAmIResponse->getUserId(), $userResponse->getFirstName(), $userResponse->getLastName());
	    $this->authenticationCache->storeUserIdentity($user);
	    //Store the authentication token and identity.
	    $this->authenticationCache->storeAuthenticationToken($token);
        
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
            return new OnlineAuthentication($url, $username, $password, $this->soapRequester, $this->twig, $this->logger);
        } else {
            return new OnPremisesAuthentication($url, $username, $password, $this->soapRequester, $this->twig, $this->logger);
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