# Cups IPP

CUPS Implementation of IPP - PHP Client API


## Install via Composer

````sh
composer require smalot/cups-ipp
````

Then, require the `vendor/autoload.php` file to enable the autoloading mechanism provided by Composer.
Otherwise, your application won't be able to find the classes of this component.


# Requirements

This library use unix sock connection: `unix:///var/run/cups/cups.sock`

First of all, check if you have correct access to this file: `/var/run/cups/cups.sock`


## Implementation

List printers

````php
<?php

include 'vendor/autoload.php';

use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

$client = new Client();
$builder = new Builder();
$responseParser = new ResponseParser();

$printerManager = new PrinterManager($builder, $client, $responseParser);
$printers = $printerManager->getList();

foreach ($printers as $printer) {
    echo $printer->getName().' ('.$printer->getUri().')'.PHP_EOL;
}

````
