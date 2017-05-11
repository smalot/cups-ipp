# Cups IPP

CUPS Implementation of IPP - PHP Client API

CUPS (Common Unix Printing System) is a modular printing system for Unix-like computer operating systems which allows a computer to act as a print server. A computer running CUPS is a host that can accept print jobs from client computers, process them, and send them to the appropriate printer.

[![Build Status](https://travis-ci.org/smalot/cups-ipp.png?branch=master)](https://travis-ci.org/smalot/cups-ipp)
[![Current Version](https://poser.pugx.org/smalot/cups-ipp/v/stable.png)](https://packagist.org/packages/smalot/cups-ipp)
[![composer.lock](https://poser.pugx.org/smalot/cups-ipp/composerlock)](https://packagist.org/packages/smalot/cups-ipp)

[![Total Downloads](https://poser.pugx.org/smalot/cups-ipp/downloads.png)](https://packagist.org/packages/smalot/cups-ipp)
[![Monthly Downloads](https://poser.pugx.org/smalot/cups-ipp/d/monthly)](https://packagist.org/packages/smalot/cups-ipp)
[![Daily Downloads](https://poser.pugx.org/smalot/cups-ipp/d/daily)](https://packagist.org/packages/smalot/cups-ipp)


## Install via Composer

You can install the component using [Composer](https://getcomposer.org/).

````sh
composer require smalot/cups-ipp
````

Then, require the `vendor/autoload.php` file to enable the autoloading mechanism provided by Composer.
Otherwise, your application won't be able to find the classes of this component.


## Requirements

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


### List all printer's jobs

````php
<?php

include 'vendor/autoload.php';

use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\JobManager;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

$client = new Client();
$builder = new Builder();
$responseParser = new ResponseParser();

$printerManager = new PrinterManager($builder, $client, $responseParser);
$printer = $printerManager->findByUri('ipp://localhost:631/printers/HP-Photosmart-C4380-series');

$jobManager = new JobManager($builder, $client, $responseParser);
$jobs = $jobManager->getList($printer, false, 0, 'completed');

foreach ($jobs as $job) {
    echo '#'.$job->getId().' '.$job->getName().' - '.$job->getState().PHP_EOL;
}

````


### Create and send a new job

````php
<?php

include 'vendor/autoload.php';

use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\JobManager;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

$client = new Client();
$builder = new Builder();
$responseParser = new ResponseParser();

$printerManager = new PrinterManager($builder, $client, $responseParser);
$printer = $printerManager->findByUri('ipp://localhost:631/printers/HP-Photosmart-C4380-series');

$jobManager = new JobManager($builder, $client, $responseParser);

$job = new Job();
$job->setName('job create file');
$job->setUsername('demo');
$job->setCopies(1);
$job->setPageRanges('1');
$job->addFile('./helloworld.pdf');
$job->addAttribute('media', 'A4');
$job->addAttribute('fit-to-page', true);
$result = $jobManager->send($printer, $job);

````
