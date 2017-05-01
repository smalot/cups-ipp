<?php


namespace Smalot\Cups\Manager;

use Smalot\Cups\CupsException;

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
        $this->initTags();
    }

    /**
     *
     */
    protected function initTags()
    {
        $this->tagsTypes = [
          'unsupported' => [
            'tag' => chr(0x10),
            'build' => '',
          ],
          'reserved' => [
            'tag' => chr(0x11),
            'build' => '',
          ],
          'unknown' => [
            'tag' => chr(0x12),
            'build' => '',
          ],
          'no-value' => [
            'tag' => chr(0x13),
            'build' => 'no_value',
          ],
          'integer' => [
            'tag' => chr(0x21),
            'build' => 'integer',
          ],
          'boolean' => [
            'tag' => chr(0x22),
            'build' => 'boolean',
          ],
          'enum' => [
            'tag' => chr(0x23),
            'build' => 'enum',
          ],
          'octetString' => [
            'tag' => chr(0x30),
            'build' => 'octet_string',
          ],
          'datetime' => [
            'tag' => chr(0x31),
            'build' => 'datetime',
          ],
          'resolution' => [
            'tag' => chr(0x32),
            'build' => 'resolution',
          ],
          'rangeOfInteger' => [
            'tag' => chr(0x33),
            'build' => 'range_of_integers',
          ],
          'textWithLanguage' => [
            'tag' => chr(0x35),
            'build' => 'string',
          ],
          'nameWithLanguage' => [
            'tag' => chr(0x36),
            'build' => 'string',
          ],
            /*
               'text' => array ('tag' => chr(0x40),
               'build' => 'string'),
               'text string' => array ('tag' => chr(0x40),
               'build' => 'string'),
             */
          'textWithoutLanguage' => [
            'tag' => chr(0x41),
            'build' => 'string',
          ],
          'nameWithoutLanguage' => [
            'tag' => chr(0x42),
            'buid' => 'string',
          ],
          'keyword' => [
            'tag' => chr(0x44),
            'build' => 'string',
          ],
          'uri' => [
            'tag' => chr(0x45),
            'build' => 'string',
          ],
          'uriScheme' => [
            'tag' => chr(0x46),
            'build' => 'string',
          ],
          'charset' => [
            'tag' => chr(0x47),
            'build' => 'string',
          ],
          'naturalLanguage' => [
            'tag' => chr(0x48),
            'build' => 'string',
          ],
          'mimeMediaType' => [
            'tag' => chr(0x49),
            'build' => 'string',
          ],
          'extendedAttributes' => [
            'tag' => chr(0x7F),
            'build' => 'extended',
          ],
        ];
        $this->operationTags = [
          'compression' => [
            'tag' => 'keyword',
          ],
          'document-natural-language' => [
            'tag' => 'naturalLanguage',
          ],
          'job-k-octets' => [
            'tag' => 'integer',
          ],
          'job-impressions' => [
            'tag' => 'integer',
          ],
          'job-media-sheets' => [
            'tag' => 'integer',
          ],
        ];
        $this->jobTags = [
          'job-priority' => [
            'tag' => 'integer',
          ],
          'job-hold-until' => [
            'tag' => 'keyword',
          ],
          'job-sheets' => [
            'tag' => 'keyword',
          ], //banner page
          'multiple-document-handling' => [
            'tag' => 'keyword',
          ],
            //'copies' => array('tag' => 'integer'),
          'finishings' => [
            'tag' => 'enum',
          ],
            //'page-ranges' => array('tag' => 'rangeOfInteger'), // has its own function
            //'sides' => array('tag' => 'keyword'), // has its own function
          'number-up' => [
            'tag' => 'integer',
          ],
          'orientation-requested' => [
            'tag' => 'enum',
          ],
          'media' => [
            'tag' => 'keyword',
          ],
          'printer-resolution' => [
            'tag' => 'resolution',
          ],
          'print-quality' => [
            'tag' => 'enum',
          ],
          'job-message-from-operator' => [
            'tag' => 'textWithoutLanguage',
          ],
        ];
        $this->printerTags = [
          'requested-attributes' => [
            'tag' => 'keyword',
          ],
        ];
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
     * @return string
     */
    protected function buildJobAttributes()
    {
        $attributes = '';

        foreach ($this->jobTags as $key => $values) {
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

        reset($this->jobTags);

        return $attributes;
    }

    /**
     * @return string
     */
    protected function buildPrinterAttributes()
    {
        $attributes = '';

        foreach ($this->printerTags as $key => $values) {
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

        reset($this->printerTags);

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
        . $this->getStringLength('job-uri')
        . 'job-uri'
        . $this->getStringLength($uri)
        . $uri;

        return $metaJobUrl;
    }
}
