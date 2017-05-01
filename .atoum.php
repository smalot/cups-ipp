<?php

include 'vendor/autoload.php';

use mageekguy\atoum;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

ini_set('display_errors', 1);
error_reporting(-1);
ErrorHandler::register();
if ('cli' !== php_sapi_name()) {
    ExceptionHandler::register();
}

$coverageField = new atoum\report\fields\runner\coverage\html('CupsIpp', 'coverage');
$coverageField->setRootUrl('http://test.local');

$report = $script->addDefaultReport();
$report->addField($coverageField);
