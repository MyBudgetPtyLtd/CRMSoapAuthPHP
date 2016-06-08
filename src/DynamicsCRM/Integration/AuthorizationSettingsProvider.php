<?php
namespace DynamicsCRM\Integration;

abstract class AuthorizationSettingsProvider {
    public abstract function getCRMUri();
    public abstract function getUsername();
    public abstract function getPassword();
}