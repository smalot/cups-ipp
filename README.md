# Cups IPP

CUPS Implementation of IPP - PHP Client API

[![Build Status](https://travis-ci.org/smalot/cups-ipp.png?branch=master)](https://travis-ci.org/smalot/cups-ipp)
[![Current Version](https://poser.pugx.org/smalot/cups-ipp/v/stable.png)](https://packagist.org/packages/smalot/cups-ipp)
[![HHVM Status](http://hhvm.h4cc.de/badge/smalot/cups-ipp.png)](http://hhvm.h4cc.de/package/smalot/cups-ipp)
[![composer.lock](https://poser.pugx.org/smalot/cups-ipp/composerlock)](https://packagist.org/packages/smalot/cups-ipp)

[![Total Downloads](https://poser.pugx.org/smalot/cups-ipp/downloads.png)](https://packagist.org/packages/smalot/cups-ipp)
[![Monthly Downloads](https://poser.pugx.org/smalot/cups-ipp/d/monthly)](https://packagist.org/packages/smalot/cups-ipp)
[![Daily Downloads](https://poser.pugx.org/smalot/cups-ipp/d/daily)](https://packagist.org/packages/smalot/cups-ipp)


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

### List printers


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
