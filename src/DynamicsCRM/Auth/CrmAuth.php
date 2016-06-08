<?php
namespace DynamicsCRM\Auth;

use DynamicsCRM\Auth\Token\AuthenticationToken;
use DynamicsCRM\Http\SoapRequester;
use Psr\Log\LoggerInterface;

abstract class CrmAuth {

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
     * @param String $url
     *            The Url of the CRM Online organization (https://org.crm.dynamics.com).
     * @param String $username
     *            Username of a valid CRM user.
     * @param String $password
     *            Password of a valid CRM user.
     * @param LoggerInterface $logger
     */
    function __construct($url, $username, $password, SoapRequester $soapRequester, LoggerInterface $logger) {
        $this->url = $url;
        $logger->info("wtf? ".$this->url);
        $this->username = $username;
        $this->password = $password;
        $this->logger = $logger;
        $this->soapRequester = $soapRequester;
    }

    /**
     * Gets a CRM On Premise SOAP header & expiration.
     *
     * @return AuthenticationToken An object containing the SOAP header and expiration date/time of the header.
     */
    public abstract function Authenticate();
}

