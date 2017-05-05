<?php


namespace Smalot\Cups\Manager;

use Smalot\Cups\CupsException;
use Symfony\Component\Yaml\Parser;

/**
 * Class ManagerAbstract
 *
 * @package Smalot\Cups\Manager
 */
class ManagerAbstract
{

    use Traits\CharsetAware;
    use Traits\LanguageAware;
    use Traits\OperationIdAware;
    use Traits\UsernameAware;

    /**
     * @var array
     */
    protected $tagsTypes;

    /**
     * @var array
     */
    protected $operationTags;

    /**
     * @var array
     */
    protected $jobTags;

    /**
     * @var array
     */
    protected $printerTags;

    /**
     * ManagerAbstract constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     *
     */
    protected function init()
    {
        $path = __DIR__.'/../../config/';
        $parser = new Parser();

        $content = file_get_contents($path.'type.yml');
        $this->tagsTypes = $parser->parse($content);

        $content = file_get_contents($path.'operation.yml');
        $this->operationTags = $parser->parse($content);

        $content = file_get_contents($path.'job.yml');
        $this->jobTags = $parser->parse($content);

        $content = file_get_contents($path.'printer.yml');
        $this->printerTags = $parser->parse($content);
    }

    /**
     * @param string $string
     *
     * @return string
     * @throws \Smalot\Cups\CupsException
     */
    protected function getStringLength($string)
    {
        $length = strlen($string);

        if ($length > ((0xFF << 8) + 0xFF)) {
            $message = sprintf(
              'max string length for an ipp meta-information = %d, while here %d',
              ((0xFF << 8) + 0xFF),
              $length
            );

            throw new CupsException($message);
        }

        $int1 = $length & 0xFF;
        $length -= $int1;
        $length = $length >> 8;
        $int2 = $length & 0xFF;

        return chr($int2).chr($int1);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function buildInteger($value)
    {
        if ($value >= 2147483647 || $value < -2147483648) {
            trigger_error(
              _('Values must be between -2147483648 and 2147483647: assuming "0"'),
              E_USER_WARNING
            );

            return chr(0x00).chr(0x00).chr(0x00).chr(0x00);
        }

        $initial_value = $value;
        $int1 = $value & 0xFF;
        $value -= $int1;
        $value = $value >> 8;
        $int2 = $value & 0xFF;
        $value -= $int2;
        $value = $value >> 8;
        $int3 = $value & 0xFF;
        $value -= $int3;
        $value = $value >> 8;
        $int4 = $value & 0xFF; //64bits

        if ($initial_value < 0) {
            $int4 = chr($int4) | chr(0x80);
        } else {
            $int4 = chr($int4);
        }

        $value = $int4.chr($int3).chr($int2).chr($int1);

        return $value;
    }

    /**
     * @return string
     */
    protected function buildOperationAttributes()
    {
        $attributes = '';

        foreach ($this->operationTags as $key => $values) {
            $item = 0;

            if (array_key_exists('value', $values)) {
                foreach ($values['value'] as $item_value) {
                    if ($item == 0) {
                        $attributes .=
                          $values['systag']
                          .$this->getStringLength($key)
                          .$key
                          .$this->getStringLength($item_value)
                          .$item_value;
                    } else {
                        $attributes .=
                          $values['systag']
                          .$this->getStringLength('')
                          .$this->getStringLength($item_value)
                          .$item_value;
                    }
                    $item++;
                }
            }
        }

        reset($this->operationTags);

        return $attributes;
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    protected function buildPrinterURI($uri)
    {
        $length = strlen($uri);
        $length = chr($length);

        while (strlen($length) < 2) {
            $length = chr(0x00).$length;
        }

        $metaPrinterUrl = chr(0x45) // uri type | value-tag
          .chr(0x00).chr(0x0B) // name-length
          .'printer-uri' // printer-uri | name
          .$length.$uri;

        return $metaPrinterUrl;
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    protected function buildJobURI($uri)
    {
        $metaJobUrl = chr(0x45) // URI
          .$this->getStringLength('job-uri')
          .'job-uri'
          .$this->getStringLength($uri)
          .$uri;

        return $metaJobUrl;
    }
}
