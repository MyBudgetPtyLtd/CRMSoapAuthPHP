<?php
namespace DynamicsCRM\Integration;

class ConfigFileSettingsProvider extends AuthorizationSettingsProvider {

    private $Username;
    private $Password;
    private $Uri;

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

        $this->Username = $CRMUsername;
        $this->Password = $CRMPassword;
        $this->Uri = $CRMUri;

    }

    public function getCRMUri()
    {
        return $this->Uri;
    }

    public function getUsername()
    {
        return $this->Username;
    }

    public function getPassword()
    {
        return $this->Password;
    }
}