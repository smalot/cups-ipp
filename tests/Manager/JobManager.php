<?php

namespace Smalot\Cups\Tests\Units\Manager;

use mageekguy\atoum;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Transport\Client;

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
        $client = Client::create();

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client);
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
        $client = Client::create();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client);
        $jobs = $jobManager->getList($printer, false, 0, 'completed');

//        $this->array($jobs)->isEmpty();
    }

    public function testCreateJob()
    {
        $user = getenv('USER');
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        $client = Client::create();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client);
        $jobs = $jobManager->getList($printer, false);
        $this->array($jobs)->isEmpty();

        // Create new Job.
        $job = new Job();
        $job->setId(0);
        $job->setName('Job create test');
        $job->setUsername($user);
        $job->setCopies(1);
        $job->setPageRanges('1');
        $job->addText('hello world', 'hello');
        $result = $jobManager->create($printer, $job);

        $this->boolean($result)->isTrue();
        $this->integer($job->getId())->isGreaterThan(0);
        $this->string($job->getState())->isEqualTo('processing'); // completed ?
        $this->string($job->getPrinterUri())->isEqualTo($printer->getUri());
        $this->string($job->getPrinterUri())->isEqualTo($printerUri);

        $jobs = $jobManager->getList($printer, false);
//        $this->array($jobs)->isNotEmpty();
    }

    public function testGetList()
    {
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $builder = new Builder();
        $client = Client::create();

        $printer = new Printer();
        $printer->setUri($printerUri);

        $jobManager = new \Smalot\Cups\Manager\JobManager($builder, $client);
        $jobs = $jobManager->getList($printer, false, 0, 'completed');

        $this->array($jobs)->isNotEmpty();
    }
}
