<?php
namespace DynamicsCRM\Integration;

abstract class DynamicsCRMSettingsProvider {
    public abstract function getCRMUri();
    public abstract function getUsername();
    public abstract function getPassword();
    public abstract function getTemplateCachePath();

}