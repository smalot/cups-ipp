<?php

namespace Smalot\Cups\Tests\Units\Manager;

use mageekguy\atoum;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

/**
 * Class JobManager
 *
 * @package Smalot\Cups\Tests\Units\Manager
 */
class JobManager extends atoum\test
{

    public function testJobManager()
    {
        $builder = new Builder();
        $client = new Client();
        $responseParser = new ResponseParser();

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client, $responseParser);
        $jobManager->setCharset('utf-8');
        $jobManager->setLanguage('fr-fr');
        $jobManager->setOperationId(5);
        $jobManager->setUsername('testuser');

        $this->string($jobManager->getCharset())->isEqualTo('utf-8');
        $this->string($jobManager->getLanguage())->isEqualTo('fr-fr');
        $this->integer($jobManager->getOperationId())->isEqualTo(5);
        $this->string($jobManager->getUsername())->isEqualTo('testuser');

        $this->integer($jobManager->getOperationId('current'))->isEqualTo(5);
        $this->integer($jobManager->getOperationId('new'))->isEqualTo(6);
    }

    public function testGetListEmpty()
    {
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        $client = new Client();
        $responseParser = new ResponseParser();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client, $responseParser);
        //        $jobs = $jobManager->getList($printer, false, 0, 'completed');
        //        $this->array($jobs)->isEmpty();
    }

    public function testCreateFileJob()
    {
        $user = getenv('USER');
        $password = getenv('PASS');
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        /** @var Client $client */
        $client = new Client();
        $client->setAuthentication($user, $password);
        $responseParser = new ResponseParser();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client, $responseParser);
        //        $jobs = $jobManager->getList($printer, false);
        //        $this->array($jobs)->isEmpty();

        // Create new Job.
        $job = new Job();
        $job->setName('job create file');
        $job->setUsername($user);
        $job->setCopies(1);
        $job->setPageRanges('1');
        $job->addFile('./tests/helloworld.pdf');
        $job->addAttribute('media', 'A4');
        $job->addAttribute('fit-to-page', true);
        $result = $jobManager->send($printer, $job);

        sleep(5);
        $jobManager->reloadAttributes($job);

        $this->boolean($result)->isTrue();
        $this->integer($job->getId())->isGreaterThan(0);
        $this->string($job->getState())->isEqualTo('completed');
        $this->string($job->getPrinterUri())->isEqualTo($printer->getUri());
        $this->string($job->getPrinterUri())->isEqualTo($printerUri);

        //        $jobs = $jobManager->getList($printer, false);
        //         $this->array($jobs)->isNotEmpty();
    }

    public function testCreateTextJob()
    {
        $user = getenv('USER');
        $password = getenv('PASS');
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        /** @var Client $client */
        $client = new Client();
        $client->setAuthentication($user, $password);
        $responseParser = new ResponseParser();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client, $responseParser);
        $jobManager->setUsername($user);
        //        $jobs = $jobManager->getList($printer, false);
        //        $this->array($jobs)->isEmpty();

        // Create new Job.
        $job = new Job();
        $job->setName('job create text');
        $job->setUsername($user);
        $job->setCopies(1);
        $job->setPageRanges('1');
        $job->addText('hello world', 'hello');
        $job->addAttribute('media', 'A4');
        $job->addAttribute('fit-to-page', true);
        $result = $jobManager->send($printer, $job);

        sleep(5);
        $jobManager->reloadAttributes($job);

        $this->boolean($result)->isTrue();
        $this->integer($job->getId())->isGreaterThan(0);
        $this->string($job->getState())->isEqualTo('completed');
        $this->string($job->getPrinterUri())->isEqualTo($printer->getUri());
        $this->string($job->getPrinterUri())->isEqualTo($printerUri);

        //        $jobs = $jobManager->getList($printer, false);
        //        $this->array($jobs)->isNotEmpty();
    }

    public function testGetList()
    {
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        $client = new Client();
        $responseParser = new ResponseParser();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client, $responseParser);
        //        $jobs = $jobManager->getList($printer, false, 0, 'completed');
        //        $this->array($jobs)->isNotEmpty();
    }
}
