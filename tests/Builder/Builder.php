<?php

namespace Smalot\Cups\Tests\Units\Builder;

use mageekguy\atoum;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Transport\Client;

/**
 * Class Builder
 *
 * @package Smalot\Cups\Tests\Units\Builder
 */
class Builder extends atoum\test
{

    public function testFormatStringLength()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $length = $builder->formatStringLength('bonjour');
        $this->string($length)->isEqualTo(chr(0).chr(7));
        $length = $builder->formatStringLength(str_repeat('X', 512));
        $this->string($length)->isEqualTo(chr(2).chr(0));
        $length = $builder->formatStringLength(str_repeat('X', 513));
        $this->string($length)->isEqualTo(chr(2).chr(1));
        $length = $builder->formatStringLength(str_repeat('X', 65535));
        $this->string($length)->isEqualTo(chr(255).chr(255));

        $this->exception(
          function () use ($builder) {
              $builder->formatStringLength(str_repeat('X', 65535 + 1));
          }
        )
          ->isInstanceOf('\Smalot\Cups\CupsException')
          ->hasMessage('Max string length for an ipp meta-information = 65535, while here 65536.')
          ->hasCode(0);
    }

    public function testFormatInteger()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $length = $builder->formatInteger(0);
        $this->string($length)->isEqualTo(chr(0).chr(0).chr(0).chr(0));
        $length = $builder->formatInteger(5);
        $this->string($length)->isEqualTo(chr(0).chr(0).chr(0).chr(5));
        $length = $builder->formatInteger(-5);
        $this->string($length)->isEqualTo(chr(255).chr(255).chr(255).chr(251));
        $length = $builder->formatInteger(1024);
        $this->string($length)->isEqualTo(chr(0).chr(0).chr(4).chr(0));
        $length = $builder->formatInteger(65535);
        $this->string($length)->isEqualTo(chr(0).chr(0).chr(255).chr(255));
        $length = $builder->formatInteger(2147483646);
        $this->string($length)->isEqualTo(chr(127).chr(255).chr(255).chr(254));
        $length = $builder->formatInteger(-2147483648);
        $this->string($length)->isEqualTo(chr(128).chr(0).chr(0).chr(0));

        $this->exception(
          function () use ($builder) {
              $builder->formatInteger(2147483647);
          }
        )
          ->isInstanceOf('\Smalot\Cups\CupsException')
          ->hasMessage('Values must be between -2147483648 and 2147483647.')
          ->hasCode(0);
        $this->exception(
          function () use ($builder) {
              $builder->formatInteger(-2147483649);
          }
        )
          ->isInstanceOf('\Smalot\Cups\CupsException')
          ->hasMessage('Values must be between -2147483648 and 2147483647.')
          ->hasCode(0);
    }

    public function testFormatRangeOfInteger()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $length = $builder->formatRangeOfInteger('1:5');
        $this->string($length)->isEqualTo(chr(0).chr(0).chr(0).chr(1).chr(0).chr(0).chr(0).chr(5));
        $length = $builder->formatRangeOfInteger('1-5');
        $this->string($length)->isEqualTo(chr(0).chr(0).chr(0).chr(1).chr(0).chr(0).chr(0).chr(5));
        $length = $builder->formatRangeOfInteger('5');
        $this->string($length)->isEqualTo(chr(0).chr(0).chr(0).chr(5).chr(0).chr(0).chr(0).chr(5));
    }

    public function testBuildProperty()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $type = $builder->buildProperty('fit-to-page', 1);
        $this->string($type)->isEqualTo(
          chr(0x21).
          chr(0).chr(0x0b).
          'fit-to-page'.
          chr(0).chr(0x4).
          chr(0).chr(0).chr(0).chr(1)
        );
        $type = $builder->buildProperty('fit-to-page', 0);
        $this->string($type)->isEqualTo(
          chr(0x21).
          chr(0).chr(0x0b).
          'fit-to-page'.
          chr(0).chr(0x4).
          chr(0).chr(0).chr(0).chr(0)
        );

        $type = $builder->buildProperty('printer-resolution', '300dpi-300dpi');
        $this->string($type)->isEqualTo(
          chr(0x32).
          chr(0).chr(0x12).
          'printer-resolution'.
          chr(0).chr(0x9).
          chr(0).chr(0).chr(0x01).chr(0x2c).chr(0x0).chr(0x0).chr(0x01).chr(0x2c).chr(0x3)
        );

        $type = $builder->buildProperty('printer-resolution', '300dpc-300dpc');
        $this->string($type)->isEqualTo(
          chr(0x32).
          chr(0).chr(0x12).
          'printer-resolution'.
          chr(0).chr(0x9).
          chr(0).chr(0).chr(0x01).chr(0x2c).chr(0x0).chr(0x0).chr(0x01).chr(0x2c).chr(0x4)
        );

        $type = $builder->buildProperty('printer-resolution', '100x100');
        $this->string($type)->isEqualTo(
          chr(0x32).
          chr(0).chr(0x12).
          'printer-resolution'.
          chr(0).chr(0x8).
          chr(0).chr(0).chr(0x0).chr(0x64).chr(0x0).chr(0x0).chr(0x0).chr(0x64)
        );

        $type = $builder->buildProperty('orientation-requested', 'landscape');
        $this->string($type)->isEqualTo(
          chr(0x23).
          chr(0).chr(21).
          'orientation-requested'.
          chr(0).chr(0x9).
          'landscape'
        );

        $jobUri = $builder->buildProperty('printer-uri', 'http://localhost/printer/pdf');
        $this->string($jobUri)->isEqualTo(
          chr(0x45).
          chr(0).chr(11).
          'printer-uri'.
          chr(0).chr(28).
          'http://localhost/printer/pdf'
        );

        $jobUri = $builder->buildProperty('job-uri', 'http://localhost/job/8');
        $this->string($jobUri)->isEqualTo(
          chr(0x45).
          chr(0).chr(7).
          'job-uri'.
          chr(0).chr(22).
          'http://localhost/job/8'
        );

        $jobUri = $builder->buildProperty('purge-jobs', true);
        $this->string($jobUri)->isEqualTo(
          chr(0x22).
          chr(0).chr(10).
          'purge-jobs'.
          chr(0).chr(0x01).
          chr(0x01)
        );
    }

    public function testGetTypeFromProperty()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $type = $builder->getTypeFromProperty('fit-to-page');
        $this->string($type)->isEqualTo('integer');

        $this->exception(
          function () use ($builder) {
              $builder->getTypeFromProperty('property not defined');
          }
        )->hasMessage('Property not found: "property not defined".');
    }

    public function testGetTagByType()
    {
        $builder = new \Smalot\Cups\Builder\Builder();

        $type = $builder->getTagFromType('unsupported');
        $this->string($type)->isEqualTo(chr(0x10));
        $type = $builder->getTagFromType('reserved');
        $this->string($type)->isEqualTo(chr(0x11));
        $type = $builder->getTagFromType('unknown');
        $this->string($type)->isEqualTo(chr(0x12));
        $type = $builder->getTagFromType('no-value');
        $this->string($type)->isEqualTo(chr(0x13));
        $type = $builder->getTagFromType('integer');
        $this->string($type)->isEqualTo(chr(0x21));
        $type = $builder->getTagFromType('boolean');
        $this->string($type)->isEqualTo(chr(0x22));
        $type = $builder->getTagFromType('enum');
        $this->string($type)->isEqualTo(chr(0x23));
        $type = $builder->getTagFromType('octetString');
        $this->string($type)->isEqualTo(chr(0x30));
        $type = $builder->getTagFromType('datetime');
        $this->string($type)->isEqualTo(chr(0x31));
        $type = $builder->getTagFromType('resolution');
        $this->string($type)->isEqualTo(chr(0x32));
        $type = $builder->getTagFromType('rangeOfInteger');
        $this->string($type)->isEqualTo(chr(0x33));
        $type = $builder->getTagFromType('textWithLanguage');
        $this->string($type)->isEqualTo(chr(0x35));
        $type = $builder->getTagFromType('nameWithLanguage');
        $this->string($type)->isEqualTo(chr(0x36));
        $type = $builder->getTagFromType('textWithoutLanguage');
        $this->string($type)->isEqualTo(chr(0x41));
        $type = $builder->getTagFromType('nameWithoutLanguage');
        $this->string($type)->isEqualTo(chr(0x42));
        $type = $builder->getTagFromType('keyword');
        $this->string($type)->isEqualTo(chr(0x44));
        $type = $builder->getTagFromType('uri');
        $this->string($type)->isEqualTo(chr(0x45));
        $type = $builder->getTagFromType('uriScheme');
        $this->string($type)->isEqualTo(chr(0x46));
        $type = $builder->getTagFromType('charset');
        $this->string($type)->isEqualTo(chr(0x47));
        $type = $builder->getTagFromType('naturalLanguage');
        $this->string($type)->isEqualTo(chr(0x48));
        $type = $builder->getTagFromType('mimeMediaType');
        $this->string($type)->isEqualTo(chr(0x49));
        $type = $builder->getTagFromType('extendedAttributes');
        $this->string($type)->isEqualTo(chr(0x7F));

        $type = $builder->getTagFromType('unsupported');
        $this->string($type)->isNotEqualTo(chr(0x11));
    }
}
