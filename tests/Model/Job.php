<?php

namespace Smalot\Cups\Tests\Units\Model;

use mageekguy\atoum;

/**
 * Class Job
 *
 * @package Smalot\Cups\Tests\Units\Model
 */
class Job extends atoum\test
{

    public function testJob()
    {
        $job = new \Smalot\Cups\Model\Job();
        $job->setId(1);
        $job->setName('Job #1');
        $job->setUri('ipp://localhost:631/printers/PDF/1');
        $job->setPrinterUri('ipp://localhost:631/printers/PDF');
        $job->setUsername('testuser');
        $job->setSides(2);
        $job->setCopies(3);
        $job->setPageRanges('1-2,4-6');
        $job->setFidelity(1);
        $job->setState('idle');
        $job->setStateReason('Not working');
        $job->setAttributes(['job-id' => 8]);

        $job->addText('simple text', 'no name');
        $job->addFile('filename.ext', 'text/plain', 'my file');

        $this->integer($job->getId())->isEqualTo(1);
        $this->string($job->getName())->isEqualTo('Job #1');
        $this->string($job->getUri())->isEqualTo('ipp://localhost:631/printers/PDF/1');
        $this->string($job->getPrinterUri())->isEqualTo('ipp://localhost:631/printers/PDF');
        $this->string($job->getUsername())->isEqualTo('testuser');
        $this->integer($job->getSides())->isEqualTo(2);
        $this->integer($job->getCopies())->isEqualTo(3);
        $this->string($job->getPageRanges())->isEqualTo('1-2,4-6');
        $this->integer($job->getFidelity())->isEqualTo(1);
        $this->string($job->getState())->isEqualTo('idle');
        $this->string($job->getStateReason())->isEqualTo('Not working');
        $this->array($job->getAttributes())->isEqualTo(['job-id' => 8]);

        $content = $job->getContent();
        $this->array($content)->hasSize(2);
        $this->array($content[0])->isEqualTo(
          [
            'type' => 'text',
            'text' => 'simple text',
            'name' => 'no name',
          ]
        );
        $this->array($content[1])->isEqualTo(
          [
            'type' => 'file',
            'filename' => 'filename.ext',
            'mimetype' => 'text/plain',
            'name' => 'my file',
          ]
        );
    }
}
