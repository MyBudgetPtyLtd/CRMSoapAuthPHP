<?php
namespace DynamicsCRM\Auth;

use DynamicsCRM\Auth\Token\AuthenticationToken;

abstract class CrmAuth {

    protected $Url;
    protected $Username;
    protected $Password;

    /**
    * @param String $username
    *        	Username of a valid CRM user.
    * @param String $password
    *        	Password of a valid CRM user.
    * @param String $url
    *        	The Url of the CRM Online organization (https://org.crm.dynamics.com).
    */
    function __construct($url, $username, $password) {
        $this->Url = $url;
        $this->Username = $username;
        $this->Password = $password;
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
    public static function Create($url, $username, $password) {
        if (strpos ( strtoupper ( $url ), ".DYNAMICS.COM" )) {
            return new CrmOnlineAuth($url, $username, $password);
        } else {
            return new CrmOnPremisesAuth($url, $username, $password);
        }
    }

    /**
     * Gets a CRM On Premise SOAP header & expiration.
     *
     * @return AuthenticationToken An object containing the SOAP header and expiration date/time of the header.
     */
    public abstract function Authenticate();
}

