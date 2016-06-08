<?php

use DynamicsCRM\DynamicsCRM;
use DynamicsCRM\Integration\ConfigFileSettingsProvider;
use DynamicsCRM\Integration\SingleRequestAuthorizationCache;
use DynamicsCRM\Requests\CreateLeadRequest;
use DynamicsCRM\Response\CreateEntityResponse;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

include __DIR__.'/vendor/autoload.php';

// create a log channel
$log = new Logger('name');
$handler = new StreamHandler(__DIR__ . '\example.log', Logger::WARNING);
$formatter = new \Monolog\Formatter\LineFormatter();
$formatter->allowInlineLineBreaks(true);
$handler->setFormatter($formatter);
$log->pushHandler($handler);

$authorizationSettingsProvider = new ConfigFileSettingsProvider(__DIR__ . '/config.php');

$crmExecuteSoap = new DynamicsCRM($authorizationSettingsProvider, new SingleRequestAuthorizationCache(), $log);

$request = (new CreateLeadRequest())
    ->setUserId($crmExecuteSoap->GetCurrentUserId())
    ->setFirstName("Daniel")
    ->setLastName("Radcliffe");

/** @var CreateEntityResponse $response */
$response = $crmExecuteSoap->Request($request);
$log->info($response->getEntityId());