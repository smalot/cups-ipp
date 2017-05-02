<?php

namespace Smalot\Cups\Tests\Units\Manager;

use mageekguy\atoum;
use Smalot\Cups\Transport\Client;

/**
 * Class PrinterManager
 *
 * @package Smalot\Cups\Tests\Units\Manager
 */
class PrinterManager extends atoum\test
{
    protected $printerUri = 'ipp://localhost:631/printers/PDF';

    public function testPrinterManager()
    {
        $client = Client::create();

        $printerManager = new \Smalot\Cups\Manager\PrinterManager($client);
        $printerManager->setCharset('utf-8');
        $printerManager->setLanguage('fr-fr');
        $printerManager->setOperationId(5);
        $printerManager->setUsername('testuser');

        $this->string($printerManager->getCharset())->isEqualTo('utf-8');
        $this->string($printerManager->getLanguage())->isEqualTo('fr-fr');
        $this->integer($printerManager->getOperationId())->isEqualTo(5);
        $this->string($printerManager->getUsername())->isEqualTo('testuser');

        $this->integer($printerManager->getOperationId('current'))->isEqualTo(5);
        $this->integer($printerManager->getOperationId('new'))->isEqualTo(6);
    }

    public function testGetList()
    {
        $client = Client::create();

        $printerManager = new \Smalot\Cups\Manager\PrinterManager($client);
        $printers = $printerManager->getList();

        $this->array($printers)->size->isGreaterThanOrEqualTo(1);

        $found = false;
        foreach ($printers as $printer) {
            if ($printer->getName() == 'PDF') {
                $found = true;
                break;
            }
        }

        $this->boolean($found)->isEqualTo(true);
        $this->string($printer->getName())->isEqualTo('PDF');
        $this->string($printer->getUri())->isEqualTo($this->printerUri);
        $this->string($printer->getStatus())->isEqualTo('idle');
    }

    public function testPause()
    {
        $client = Client::create();
        $client->setAuthentication('travis', 'travis');

        $printerManager = new \Smalot\Cups\Manager\PrinterManager($client);
        $printerManager->pause($this->printerUri);
    }
}
