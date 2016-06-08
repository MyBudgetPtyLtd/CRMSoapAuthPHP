<?php

use DynamicsCRM\DynamicsCRM;
use DynamicsCRM\Integration\ConfigFileSettingsProvider;
use DynamicsCRM\Integration\SingleRequestAuthorizationCache;
use DynamicsCRM\Requests\CreateLeadRequest;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

include __DIR__.'/vendor/autoload.php';

// create a log channel
$log = new Logger('name');
$handler = new StreamHandler(__DIR__ . '\example.log', Logger::DEBUG);
$formatter = new \Monolog\Formatter\LineFormatter();
$formatter->allowInlineLineBreaks(true);
$handler->setFormatter($formatter);
$log->pushHandler($handler);

$authorizationSettingsProvider = new ConfigFileSettingsProvider(__DIR__ . '/config.php');

$crmExecuteSoap = new DynamicsCRM($authorizationSettingsProvider, new SingleRequestAuthorizationCache(), $log);

$request = new CreateLeadRequest();
$request->setFirstName("Daniel");
$request->setLastName("Radcliffe");

echo $crmExecuteSoap->Request($request);

/*function __autoload($class_name) {
    require_once("$class_name.php");
}*/