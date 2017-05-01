<?php

namespace Smalot\Cups\Tests\Units\Manager;

use mageekguy\atoum;
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
        $client = Client::create();

        $jobManager = new \Smalot\Cups\Manager\JobManager($client);
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

    public function testGetList()
    {
        $client = Client::create();
        $printerUri = 'ipp://localhost:631/printers/PDF';

        $jobManager = new \Smalot\Cups\Manager\JobManager($client);
        $printers = $jobManager->getList($printerUri);

        $this->array($printers);
        
    }
}
