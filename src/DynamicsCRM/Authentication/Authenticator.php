<?php
namespace DynamicsCRM\Authentication;

use DynamicsCRM\Authentication\Token\AuthenticationToken;
use DynamicsCRM\Guid;
use DynamicsCRM\Http\SoapRequester;
use Psr\Log\LoggerInterface;
use Twig_Environment;

abstract class Authenticator {

    protected $url;
    protected $username;
    protected $password;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var SoapRequester
     */
    protected $soapRequester;
    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @param String $url
     *            The Url of the CRM Online organization (https://org.crm.dynamics.com).
     * @param String $username
     *            Username of a valid CRM user.
     * @param String $password
     *            Password of a valid CRM user.
     * @param SoapRequester $soapRequester the SOAP Requester
     * @param Twig_Environment $twig A twig environment for templating
     * @param LoggerInterface $logger A PSR-3 compatible logger
     */
    function __construct($url, $username, $password, SoapRequester $soapRequester, Twig_Environment $twig, LoggerInterface $logger) {
        $this->url = $url.(substr ( $url, - 1 ) == '/' ? '' : '/');
        $this->username = $username;
        $this->password = $password;
        $this->logger = $logger;
        $this->soapRequester = $soapRequester;
        $this->twig = $twig;
    }

    public function getHeaderTemplateFilename() {
        return join('', array_slice(explode('\\', get_class($this)), -1));
    }

    /**
     * Gets a CRM On Premise SOAP header & expiration.
     *
     * @return AuthenticationToken An object containing the SOAP header and expiration date/time of the header.
     */
    public abstract function Authenticate();

    protected abstract function getUrnAddress();

    protected function getAuthenticationRequest() {
        $now = $_SERVER['REQUEST_TIME'];
        $template_name = join('', array_slice(explode('\\', get_class($this)), -1));

        $template_variables = [
            "tokenCreated" => gmdate ( 'Y-m-d\TH:i:s.u\Z', $now ),
            "tokenExpires" => gmdate ( 'Y-m-d\TH:i:s.u\Z', strtotime ( '+60 minute', $now ) ),
            "username" => $this->username,
            "password" => $this->password,
            "urnAddress" => $this->getUrnAddress(),
            "messageId" => Guid::newGuid(),
            "usernameTokenGuid" => Guid::newGuid(),
            "timestampId" => Guid::newGuid()
        ];

        $template = $this->twig->loadTemplate("@Authentication/$template_name.xml");
        return $template->render($template_variables);
    }
}

