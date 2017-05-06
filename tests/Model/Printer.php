<?php

namespace Smalot\Cups\Tests\Units\Model;

use mageekguy\atoum;

/**
 * Class Printer
 *
 * @package Smalot\Cups\Tests\Units\Model
 */
class Printer extends atoum\test
{

    public function testPrinter()
    {
        $printer = new \Smalot\Cups\Model\Printer();
        $printer->setName('PDF');
        $printer->setUri('ipp://localhost:631/printers/PDF');
        $printer->setAttributes(['printer-uri' => 'ipp://localhost:631/printers/PDF']);
        $printer->setStatus('idle');

        $this->string($printer->getName())->isEqualTo('PDF');
        $this->string($printer->getUri())->isEqualTo('ipp://localhost:631/printers/PDF');
        $this->array($printer->getAttributes())->isEqualTo(['printer-uri' => ['ipp://localhost:631/printers/PDF']]);
        $this->string($printer->getStatus())->isEqualTo('idle');
    }
}
