<?php
namespace DynamicsCRM\Integration;

class ConfigFileSettingsProvider extends DynamicsCRMSettingsProvider {

    private $username;
    private $password;
    private $uri;

    public function __construct($settingsFile)
    {
        /** @noinspection PhpIncludeInspection */
        include_once $settingsFile;

        if (!isset($CRMUsername)) {
            throw new \Exception("CRMUsername is not defined in $settingsFile");
        }
        if (!isset($CRMPassword)) {
            throw new \Exception("CRMPassword is not defined in $settingsFile");
        }
        if (!isset($CRMUri)) {
            throw new \Exception("CRMUri is not defined in $settingsFile");
        }

        $this->username = $CRMUsername;
        $this->password = $CRMPassword;
        $this->uri = $CRMUri.(substr ( $CRMUri, - 1 ) == '/' ? '' : '/');
    }

    public function getCRMUri()
    {
        return $this->uri;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }
}