<?php

use DynamicsCRM\CrmExecuteSoap;
use DynamicsCRM\Integration\ConfigFileSettingsProvider;
use DynamicsCRM\Integration\SingleRequestAuthorizationCache;
use DynamicsCRM\Requests\CreateLeadRequest;

$request = new CreateLeadRequest();
$request->setFirstName("Daniel");
$request->setLastName("Radcliffe");

$crmExecuteSoap = new CrmExecuteSoap(new ConfigFileSettingsProvider(__DIR__ . '/config.php'), new SingleRequestAuthorizationCache());
echo $crmExecuteSoap->ExecuteCRMRequest($request);

function __autoload($class_name) {
    require_once("$class_name.php");
}