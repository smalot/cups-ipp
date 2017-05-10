<?php

namespace Smalot\Cups\Transport;

use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseParser
 *
 * @package Smalot\Cups\Transport
 */
class ResponseParser
{

    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var array
     */
    protected $body = [];

    /**
     * @var int
     */
    protected $index = 0;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var mixed
     */
    protected $collection; // RFC3382

    /**
     * @var array
     */
    protected $collectionKey = []; // RFC3382

    /**
     * @var int
     */
    protected $collectionDepth = -1; // RFC3382

    /**
     * @var bool
     */
    protected $endCollection = false; // RFC3382

    /**
     * @var array
     */
    protected $collectionNbr = []; // RFC3382

    /**
     * @var string
     */
    protected $attributeName = '';

    /**
     * @var string
     */
    protected $lastAttributeName = '';

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function parse(ResponseInterface $response)
    {
        // Reset properties.
        $this->reset();

        // Run parsing.
        $this->content = $response->getBody()->getContents();
        $ippVersion = $this->parseIppVersion();
        $statusCode = $this->parseStatusCode();
        $requestId = $this->parseRequestID();
        $body = $this->parseBody();

        return $this->generateResponse($ippVersion, $statusCode, $requestId, $body);
    }

    /**
     *
     */
    protected function reset()
    {
        $this->offset = 0;
        $this->index = 0;
        $this->collection = null;
        $this->collectionKey = [];
        $this->collectionDepth = -1;
        $this->endCollection = false;
        $this->collectionNbr = [];
        $this->attributeName = '';
        $this->lastAttributeName = '';
    }

    /**
     * @param string $ippVersion
     * @param string $statusCode
     * @param int $requestId
     * @param array $body
     *
     * @return \Smalot\Cups\Transport\Response
     */
    protected function generateResponse($ippVersion, $statusCode, $requestId, $body)
    {
        return new Response($ippVersion, $statusCode, $requestId, $body);
    }

    /**
     * @return string
     */
    protected function parseIppVersion()
    {
        $text = (ord($this->content[$this->offset]) * 256) + ord($this->content[$this->offset + 1]);
        $this->offset += 2;

        switch ($text) {
            case 0x0101:
                $ippVersion = '1.1';
                break;

            default:
                $ippVersion =
                  sprintf(
                    '%u.%u (Unknown)',
                    ord($this->content[$this->offset]) * 256,
                    ord($this->content[$this->offset + 1])
                  );
                break;
        }

        return $ippVersion;
    }

    /**
     * @return string
     */
    protected function parseStatusCode()
    {
        $status_code = (ord($this->content[$this->offset]) * 256) + ord($this->content[$this->offset + 1]);
        $status = 'NOT PARSED';
        $this->offset += 2;

        if (strlen($this->content) < $this->offset) {
            return false;
        }

        if ($status_code < 0x00FF) {
            $status = 'successfull';
        } elseif ($status_code < 0x01FF) {
            $status = 'informational';
        } elseif ($status_code < 0x02FF) {
            $status = 'redirection';
        } elseif ($status_code < 0x04FF) {
            $status = 'client-error';
        } elseif ($status_code < 0x05FF) {
            $status = 'server-error';
        }

        switch ($status_code) {
            case 0x0000:
                $status = 'successfull-ok';
                break;

            case 0x0001:
                $status = 'successful-ok-ignored-or-substituted-attributes';
                break;

            case 0x002:
                $status = 'successful-ok-conflicting-attributes';
                break;

            case 0x0400:
                $status = 'client-error-bad-request';
                break;

            case 0x0401:
                $status = 'client-error-forbidden';
                break;

            case 0x0402:
                $status = 'client-error-not-authenticated';
                break;

            case 0x0403:
                $status = 'client-error-not-authorized';
                break;

            case 0x0404:
                $status = 'client-error-not-possible';
                break;

            case 0x0405:
                $status = 'client-error-timeout';
                break;

            case 0x0406:
                $status = 'client-error-not-found';
                break;

            case 0x0407:
                $status = 'client-error-gone';
                break;

            case 0x0408:
                $status = 'client-error-request-entity-too-large';
                break;

            case 0x0409:
                $status = 'client-error-request-value-too-long';
                break;

            case 0x040A:
                $status = 'client-error-document-format-not-supported';
                break;

            case 0x040B:
                $status = 'client-error-attributes-or-values-not-supported';
                break;

            case 0x040C:
                $status = 'client-error-uri-scheme-not-supported';
                break;

            case 0x040D:
                $status = 'client-error-charset-not-supported';
                break;

            case 0x040E:
                $status = 'client-error-conflicting-attributes';
                break;

            case 0x040F:
                $status = 'client-error-compression-not-supported';
                break;

            case 0x0410:
                $status = 'client-error-compression-error';
                break;

            case 0x0411:
                $status = 'client-error-document-format-error';
                break;

            case 0x0412:
                $status = 'client-error-document-access-error';
                break;

            case 0x0413: // RFC3380
                $status = 'client-error-attributes-not-settable';
                break;

            case 0x0500:
                $status = 'server-error-internal-error';
                break;

            case 0x0501:
                $status = 'server-error-operation-not-supported';
                break;

            case 0x0502:
                $status = 'server-error-service-unavailable';
                break;

            case 0x0503:
                $status = 'server-error-version-not-supported';
                break;

            case 0x0504:
                $status = 'server-error-device-error';
                break;

            case 0x0505:
                $status = 'server-error-temporary-error';
                break;

            case 0x0506:
                $status = 'server-error-not-accepting-jobs';
                break;

            case 0x0507:
                $status = 'server-error-busy';
                break;

            case 0x0508:
                $status = 'server-error-job-canceled';
                break;

            case 0x0509:
                $status = 'server-error-multiple-document-jobs-not-supported';
                break;

            default:
                break;
        }

        return $status;
    }

    /**
     * @return int
     */
    protected function parseRequestID()
    {
        $requestId = $this->interpretInteger(substr($this->content, $this->offset, 4));
        $this->offset += 4;

        return $requestId;
    }

    /**
     * @return array
     */
    protected function parseBody()
    {
        $j = -1;
        $this->index = 0;

        for ($i = $this->offset; $i < strlen($this->content); $i = $this->offset) {
            $tag = ord($this->content[$this->offset]);

            if ($tag > 0x0F) {
                $this->readAttribute($j);
                $this->index++;
                continue;
            }

            switch ($tag) {
                case 0x01:
                    $j += 1;
                    $this->body[$j]['attributes'] = 'operation-attributes';
                    $this->index = 0;
                    $this->offset += 1;
                    break;
                case 0x02:
                    $j += 1;
                    $this->body[$j]['attributes'] = 'job-attributes';
                    $this->index = 0;
                    $this->offset += 1;
                    break;
                case 0x03:
                    $j += 1;
                    $this->body[$j]['attributes'] = 'end-of-attributes';

                    return $this->body;
                case 0x04:
                    $j += 1;
                    $this->body[$j]['attributes'] = 'printer-attributes';
                    $this->index = 0;
                    $this->offset += 1;
                    break;
                case 0x05:
                    $j += 1;
                    $this->body[$j]['attributes'] = 'unsupported-attributes';
                    $this->index = 0;
                    $this->offset += 1;
                    break;
                default:
                    $j += 1;
                    $this->body[$j]['attributes'] = sprintf(
                      _('0x%x (%u) : attributes tag Unknown (reserved for future versions of IPP'),
                      $tag,
                      $tag
                    );
                    $this->index = 0;
                    $this->offset += 1;
                    break;
            }
        }

        return $this->body;
    }

    protected function readAttribute($attributes_type)
    {

        $tag = ord($this->content[$this->offset]);

        $this->offset += 1;
        $j = $this->index;

        $tag = $this->readTag($tag);

        switch ($tag) {
            case 'begCollection': //RFC3382 (BLIND CODE)
                if ($this->endCollection) {
                    $this->index--;
                }
                $this->endCollection = false;
                $this->body[$attributes_type][$j]['type'] = 'collection';
                $this->readAttributeName($attributes_type, $j);
                if (!$this->body[$attributes_type][$j]['name']) { // it is a multi-valued collection
                    $this->collectionDepth++;
                    $this->index--;
                    $this->collectionNbr[$this->collectionDepth]++;
                } else {
                    $this->collectionDepth++;
                    if ($this->collectionDepth == 0) {
                        $this->collection = (object)'collection';
                    }
                    if (array_key_exists($this->collectionDepth, $this->collectionNbr)) {
                        $this->collectionNbr[$this->collectionDepth]++;
                    } else {
                        $this->collectionNbr[$this->collectionDepth] = 0;
                    }
                    unset($this->endCollection);

                }
                $this->readValue($attributes_type, $j);
                break;
            case 'endCollection': //RFC3382 (BLIND CODE)
                $this->body[$attributes_type][$j]['type'] = 'collection';
                $this->readAttributeName($attributes_type, $j, 0);
                $this->readValue($attributes_type, $j, 0);
                $this->collectionDepth--;
                $this->collectionKey[$this->collectionDepth] = 0;
                $this->endCollection = true;
                break;
            case 'memberAttrName': // RFC3382 (BLIND CODE)
                $this->body[$attributes_type][$j]['type'] = 'memberAttrName';
                $this->index--;
                $this->readCollection($attributes_type, $j);
                break;

            default:
                $this->collectionDepth = -1;
                $this->collectionKey = [];
                $this->collectionNbr = [];
                $this->body[$attributes_type][$j]['type'] = $tag;
                $attributeName = $this->readAttributeName($attributes_type, $j);
                if (!$attributeName) {
                    $attributeName = $this->attributeName;
                } else {
                    $this->attributeName = $attributeName;
                }
                $this->readValue($attributes_type, $j);
                $this->body[$attributes_type][$j]['value'] =
                  $this->interpretAttribute(
                    $attributeName,
                    $tag,
                    $this->body[$attributes_type][$j]['value']
                  );
                break;

        }

        return;
    }

    protected function readTag($tag)
    {
        switch ($tag) {
            case 0x10:
                $tag = 'unsupported';
                break;
            case 0x11:
                $tag = 'reserved for "default"';
                break;
            case 0x12:
                $tag = 'unknown';
                break;
            case 0x13:
                $tag = 'no-value';
                break;
            case 0x15: // RFC 3380
                $tag = 'not-settable';
                break;
            case 0x16: // RFC 3380
                $tag = 'delete-attribute';
                break;
            case 0x17: // RFC 3380
                $tag = 'admin-define';
                break;
            case 0x20:
                $tag = 'IETF reserved (generic integer)';
                break;
            case 0x21:
                $tag = 'integer';
                break;
            case 0x22:
                $tag = 'boolean';
                break;
            case 0x23:
                $tag = 'enum';
                break;
            case 0x30:
                $tag = 'octetString';
                break;
            case 0x31:
                $tag = 'datetime';
                break;
            case 0x32:
                $tag = 'resolution';
                break;
            case 0x33:
                $tag = 'rangeOfInteger';
                break;
            case 0x34: //RFC3382 (BLIND CODE)
                $tag = 'begCollection';
                break;
            case 0x35:
                $tag = 'textWithLanguage';
                break;
            case 0x36:
                $tag = 'nameWithLanguage';
                break;
            case 0x37: //RFC3382 (BLIND CODE)
                $tag = 'endCollection';
                break;
            case 0x40:
                $tag = 'IETF reserved text string';
                break;
            case 0x41:
                $tag = 'textWithoutLanguage';
                break;
            case 0x42:
                $tag = 'nameWithoutLanguage';
                break;
            case 0x43:
                $tag = 'IETF reserved for future';
                break;
            case 0x44:
                $tag = 'keyword';
                break;
            case 0x45:
                $tag = 'uri';
                break;
            case 0x46:
                $tag = 'uriScheme';
                break;
            case 0x47:
                $tag = 'charset';
                break;
            case 0x48:
                $tag = 'naturalLanguage';
                break;
            case 0x49:
                $tag = 'mimeMediaType';
                break;
            case 0x4A: // RFC3382 (BLIND CODE)
                $tag = 'memberAttrName';
                break;
            case 0x7F:
                $tag = 'extended type';
                break;
            default:
                if ($tag >= 0x14 && $tag < 0x15 && $tag > 0x17 && $tag <= 0x1f) {
                    $tag = 'out-of-band';
                } elseif (0x24 <= $tag && $tag <= 0x2f) {
                    $tag = 'new integer type';
                } elseif (0x38 <= $tag && $tag <= 0x3F) {
                    $tag = 'new octet-stream type';
                } elseif (0x4B <= $tag && $tag <= 0x5F) {
                    $tag = 'new character string type';
                } elseif ((0x60 <= $tag && $tag < 0x7f) || $tag >= 0x80) {
                    $tag = 'IETF reserved for future';
                } else {
                    $tag = sprintf('UNKNOWN: 0x%x (%u)', $tag, $tag);
                }
                break;
        }

        return $tag;
    }

    protected function readCollection($attributes_type, $j)
    {
        $name_length = ord($this->content[$this->offset]) * 256 + ord($this->content[$this->offset + 1]);
        $this->offset += 2;
        $name = '';

        for ($i = 0; $i < $name_length; $i++) {
            $name .= $this->content[$this->offset];
            $this->offset += 1;
            if ($this->offset > strlen($this->content)) {
                return;
            }
        }

        $collection_name = $name;
        $name_length = ord($this->content[$this->offset]) * 256 + ord($this->content[$this->offset + 1]);
        $this->offset += 2;
        $name = '';

        for ($i = 0; $i < $name_length; $i++) {
            $name .= $this->content[$this->offset];
            $this->offset += 1;
            if ($this->offset > strlen($this->content)) {
                return;
            }
        }

        $attributeName = $name;
        if ($attributeName == '') {
            $attributeName = $this->lastAttributeName;
            $this->collectionKey[$this->collectionDepth]++;
        } else {
            $this->collectionKey[$this->collectionDepth] = 0;
        }
        $this->lastAttributeName = $attributeName;


        $tag = $this->readTag(ord($this->content[$this->offset]));
        $this->offset++;
        $type = $tag;
        $name_length = ord($this->content[$this->offset]) * 256 + ord($this->content[$this->offset + 1]);
        $this->offset += 2;
        $name = '';

        for ($i = 0; $i < $name_length; $i++) {
            $name .= $this->content[$this->offset];
            $this->offset += 1;

            if ($this->offset > strlen($this->content)) {
                return;
            }
        }

        $collection_value = $name;
        $value_length = ord($this->content[$this->offset]) * 256 + ord($this->content[$this->offset + 1]);
        $this->offset += 2;
        $value = '';

        for ($i = 0; $i < $value_length; $i++) {
            if ($this->offset >= strlen($this->content)) {
                return;
            }

            $value .= $this->content[$this->offset];
            $this->offset += 1;
        }

        $object = &$this->collection;
        for ($i = 0; $i <= $this->collectionDepth; $i++) {
            $indice = '_indice'.$this->collectionNbr[$i];
            if (!isset($object->$indice)) {
                $object->$indice = (object)'indice';
            }
            $object = &$object->$indice;
        }

        $value_key = '_value'.$this->collectionKey[$this->collectionDepth];
        $col_name_key = '_collection_name'.$this->collectionKey[$this->collectionDepth];
        $col_val_key = '_collection_value'.$this->collectionKey[$this->collectionDepth];

        $attribute_value = $this->interpretAttribute($attributeName, $tag, $value);
        $attributeName = str_replace('-', '_', $attributeName);

        $object->$attributeName->_type = $type;
        $object->$attributeName->$value_key = $attribute_value;
        $object->$attributeName->$col_name_key = $collection_name;
        $object->$attributeName->$col_val_key = $collection_value;

        $this->body[$attributes_type][$j]['value'] = $this->collection;
    }

    protected function readAttributeName($attributes_type, $j, $write = 1)
    {
        $name_length = ord($this->content[$this->offset]) * 256 + ord($this->content[$this->offset + 1]);
        $this->offset += 2;
        $name = '';

        for ($i = 0; $i < $name_length; $i++) {
            if ($this->offset >= strlen($this->content)) {
                return false;
            }
            $name .= $this->content[$this->offset];
            $this->offset += 1;
        }

        if ($write) {
            $this->body[$attributes_type][$j]['name'] = $name;
        }

        return $name;
    }

    protected function readValue($attributes_type, $j, $write = 1)
    {
        $value_length = ord($this->content[$this->offset]) * 256 + ord($this->content[$this->offset + 1]);
        $this->offset += 2;
        $value = '';

        for ($i = 0; $i < $value_length; $i++) {
            if ($this->offset >= strlen($this->content)) {
                return false;
            }
            $value .= $this->content[$this->offset];
            $this->offset += 1;
        }

        if ($write) {
            $this->body[$attributes_type][$j]['value'] = $value;
        }

        return $value;
    }

    protected function interpretAttribute($attributeName, $type, $value)
    {
        switch ($type) {
            case 'integer':
                $value = $this->interpretInteger($value);
                break;
            case 'rangeOfInteger':
                $value = $this->interpretRangeOfInteger($value);
                break;
            case 'boolean':
                $value = ord($value);
                if ($value == 0x00) {
                    $value = false;
                } else {
                    $value = true;
                }
                break;
            case 'datetime':
                $value = $this->interpretDateTime($value);
                break;
            case 'enum':
                $value = $this->interpretEnum($attributeName, $value); // must be overwritten by children
                break;
            case 'resolution':
                $unit = $value[8];
                $value = $this->interpretRangeOfInteger(substr($value, 0, 8));
                switch ($unit) {
                    case chr(0x03):
                        $unit = 'dpi';
                        break;
                    case chr(0x04):
                        $unit = 'dpc';
                        break;
                }
                $value = $value.' '.$unit;
                break;
            default:
                break;
        }

        return $value;
    }

    protected function interpretInteger($value)
    {
        // They are _signed_ integers.
        $value_parsed = 0;
        for ($i = strlen($value); $i > 0; $i--) {
            $value_parsed += ((1 << (($i - 1) * 8)) * ord($value[strlen($value) - $i]));
        }

        if ($value_parsed >= 2147483648) {
            $value_parsed -= 4294967296;
        }

        return $value_parsed;
    }

    protected function interpretRangeOfInteger($value)
    {
        $halfsize = strlen($value) / 2;
        $integer1 = $this->interpretInteger(substr($value, 0, $halfsize));
        $integer2 = $this->interpretInteger(substr($value, $halfsize, $halfsize));
        $value_parsed = sprintf('%s-%s', $integer1, $integer2);

        return $value_parsed;
    }

    protected function interpretDateTime($date)
    {
        $year = $this->interpretInteger(substr($date, 0, 2));
        $month = $this->interpretInteger(substr($date, 2, 1));
        $day = $this->interpretInteger(substr($date, 3, 1));
        $hour = $this->interpretInteger(substr($date, 4, 1));
        $minute = $this->interpretInteger(substr($date, 5, 1));
        $second = $this->interpretInteger(substr($date, 6, 1));
        $direction = substr($date, 8, 1);
        $hours_from_utc = $this->interpretInteger(substr($date, 9, 1));
        $minutes_from_utc = $this->interpretInteger(substr($date, 10, 1));

        $date = sprintf(
          '%s-%s-%s %s:%s:%s %s%s:%s',
          $year,
          $month,
          $day,
          $hour,
          $minute,
          $second,
          $direction,
          $hours_from_utc,
          $minutes_from_utc
        );

        $datetime = new \DateTime($date);

        return $datetime->format('c');
    }

    protected function interpretEnum($attributeName, $value)
    {
        $value_parsed = $this->interpretInteger($value);

        switch ($attributeName) {
            case 'job-state':
                switch ($value_parsed) {
                    case 0x03:
                        $value = 'pending';
                        break;
                    case 0x04:
                        $value = 'pending-held';
                        break;
                    case 0x05:
                        $value = 'processing';
                        break;
                    case 0x06:
                        $value = 'processing-stopped';
                        break;
                    case 0x07:
                        $value = 'canceled';
                        break;
                    case 0x08:
                        $value = 'aborted';
                        break;
                    case 0x09:
                        $value = 'completed';
                        break;
                }
                if ($value_parsed > 0x09) {
                    $value = sprintf('Unknown(IETF standards track "job-state" reserved): 0x%x', $value_parsed);
                }
                break;
            case 'print-quality':
            case 'print-quality-supported':
            case 'print-quality-default':
                switch ($value_parsed) {
                    case 0x03:
                        $value = 'draft';
                        break;
                    case 0x04:
                        $value = 'normal';
                        break;
                    case 0x05:
                        $value = 'high';
                        break;
                }
                break;
            case 'printer-state':
                switch ($value_parsed) {
                    case 0x03:
                        $value = 'idle';
                        break;
                    case 0x04:
                        $value = 'processing';
                        break;
                    case 0x05:
                        $value = 'stopped';
                        break;
                }
                if ($value_parsed > 0x05) {
                    $value = sprintf('Unknown(IETF standards track "printer-state" reserved): 0x%x', $value_parsed);
                }
                break;
            case 'printer-type':
                $value = $this::interpretPrinterType($value);
                break;

            case 'operations-supported':
                switch ($value_parsed) {
                    case 0x0000:
                    case 0x0001:
                        $value = sprintf('Unknown(reserved) : %s', ord($value));
                        break;
                    case 0x0002:
                        $value = 'Print-Job';
                        break;
                    case 0x0003:
                        $value = 'Print-URI';
                        break;
                    case 0x0004:
                        $value = 'Validate-Job';
                        break;
                    case 0x0005:
                        $value = 'Create-Job';
                        break;
                    case 0x0006:
                        $value = 'Send-Document';
                        break;
                    case 0x0007:
                        $value = 'Send-URI';
                        break;
                    case 0x0008:
                        $value = 'Cancel-Job';
                        break;
                    case 0x0009:
                        $value = 'Get-Job-Attributes';
                        break;
                    case 0x000A:
                        $value = 'Get-Jobs';
                        break;
                    case 0x000B:
                        $value = 'Get-Printer-Attributes';
                        break;
                    case 0x000C:
                        $value = 'Hold-Job';
                        break;
                    case 0x000D:
                        $value = 'Release-Job';
                        break;
                    case 0x000E:
                        $value = 'Restart-Job';
                        break;
                    case 0x000F:
                        $value = 'Unknown(reserved for a future operation)';
                        break;
                    case 0x0010:
                        $value = 'Pause-Printer';
                        break;
                    case 0x0011:
                        $value = 'Resume-Printer';
                        break;
                    case 0x0012:
                        $value = 'Purge-Jobs';
                        break;
                    case 0x0013:
                        $value = 'Set-Printer-Attributes'; // RFC3380
                        break;
                    case 0x0014:
                        $value = 'Set-Job-Attributes'; // RFC3380
                        break;
                    case 0x0015:
                        $value = 'Get-Printer-Supported-Values'; // RFC3380
                        break;
                    case 0x0016:
                        $value = 'Create-Printer-Subscriptions';
                        break;
                    case 0x0017:
                        $value = 'Create-Job-Subscriptions';
                        break;
                    case 0x0018:
                        $value = 'Get-Subscription-Attributes';
                        break;
                    case 0x0019:
                        $value = 'Get-Subscriptions';
                        break;
                    case 0x001A:
                        $value = 'Renew-Subscription';
                        break;
                    case 0x001B:
                        $value = 'Cancel-Subscription';
                        break;
                    case 0x001C:
                        $value = 'Get-Notifications';
                        break;
                    case 0x001D:
                        $value = sprintf('Unknown (reserved IETF "operations"): 0x%x', ord($value));
                        break;
                    case 0x001E:
                        $value = sprintf('Unknown (reserved IETF "operations"): 0x%x', ord($value));
                        break;
                    case 0x001F:
                        $value = sprintf('Unknown (reserved IETF "operations"): 0x%x', ord($value));
                        break;
                    case 0x0020:
                        $value = sprintf('Unknown (reserved IETF "operations"): 0x%x', ord($value));
                        break;
                    case 0x0021:
                        $value = sprintf('Unknown (reserved IETF "operations"): 0x%x', ord($value));
                        break;
                    case 0x0022:
                        $value = 'Enable-Printer';
                        break;
                    case 0x0023:
                        $value = 'Disable-Printer';
                        break;
                    case 0x0024:
                        $value = 'Pause-Printer-After-Current-Job';
                        break;
                    case 0x0025:
                        $value = 'Hold-New-Jobs';
                        break;
                    case 0x0026:
                        $value = 'Release-Held-New-Jobs';
                        break;
                    case 0x0027:
                        $value = 'Deactivate-Printer';
                        break;
                    case 0x0028:
                        $value = 'Activate-Printer';
                        break;
                    case 0x0029:
                        $value = 'Restart-Printer';
                        break;
                    case 0x002A:
                        $value = 'Shutdown-Printer';
                        break;
                    case 0x002B:
                        $value = 'Startup-Printer';
                        break;
                }

                if ($value_parsed > 0x002B && $value_parsed <= 0x3FFF) {
                    $value = sprintf('Unknown(IETF standards track operations reserved): 0x%x', $value_parsed);
                } elseif ($value_parsed >= 0x4000 && $value_parsed <= 0x8FFF) {
                    if (method_exists($this, '_getEnumVendorExtensions')) {
                        $value = $this->_getEnumVendorExtensions($value_parsed);
                    } else {
                        $value = sprintf('Unknown(Vendor extension for operations): 0x%x', $value_parsed);
                    }
                } elseif ($value_parsed > 0x8FFF) {
                    $value = sprintf('Unknown operation (should not exists): 0x%x', $value_parsed);
                }
                break;
            case 'finishings':
            case 'finishings-default':
            case 'finishings-supported':
                switch ($value_parsed) {
                    case 3:
                        $value = 'none';
                        break;
                    case 4:
                        $value = 'staple';
                        break;
                    case 5:
                        $value = 'punch';
                        break;
                    case 6:
                        $value = 'cover';
                        break;
                    case 7:
                        $value = 'bind';
                        break;
                    case 8:
                        $value = 'saddle-stitch';
                        break;
                    case 9:
                        $value = 'edge-stitch';
                        break;
                    case 20:
                        $value = 'staple-top-left';
                        break;
                    case 21:
                        $value = 'staple-bottom-left';
                        break;
                    case 22:
                        $value = 'staple-top-right';
                        break;
                    case 23:
                        $value = 'staple-bottom-right';
                        break;
                    case 24:
                        $value = 'edge-stitch-left';
                        break;
                    case 25:
                        $value = 'edge-stitch-top';
                        break;
                    case 26:
                        $value = 'edge-stitch-right';
                        break;
                    case 27:
                        $value = 'edge-stitch-bottom';
                        break;
                    case 28:
                        $value = 'staple-dual-left';
                        break;
                    case 29:
                        $value = 'staple-dual-top';
                        break;
                    case 30:
                        $value = 'staple-dual-right';
                        break;
                    case 31:
                        $value = 'staple-dual-bottom';
                        break;
                }
                if ($value_parsed > 31) {
                    $value = sprintf('Unknown(IETF standards track "finishing" reserved): 0x%x', $value_parsed);
                }
                break;

            case 'orientation-requested':
            case 'orientation-requested-supported':
            case 'orientation-requested-default':
                switch ($value_parsed) {
                    case 0x03:
                        $value = 'portrait';
                        break;
                    case 0x04:
                        $value = 'landscape';
                        break;
                    case 0x05:
                        $value = 'reverse-landscape';
                        break;
                    case 0x06:
                        $value = 'reverse-portrait';
                        break;
                }
                if ($value_parsed > 0x06) {
                    $value = sprintf('Unknown(IETF standards track "orientation" reserved): 0x%x', $value_parsed);
                }
                break;

            default:
                break;
        }

        return $value;
    }

    protected function interpretPrinterType($value)
    {
        $value_parsed = 0;

        for ($i = strlen($value); $i > 0; $i--) {
            $value_parsed += pow(256, ($i - 1)) * ord($value[strlen($value) - $i]);
        }

        $type[0] = $type[1] = $type[2] = $type[3] = $type[4] = $type[5] = '';
        $type[6] = $type[7] = $type[8] = $type[9] = $type[10] = '';
        $type[11] = $type[12] = $type[13] = $type[14] = $type[15] = '';
        $type[16] = $type[17] = $type[18] = $type[19] = '';

        if ($value_parsed % 2 == 1) {
            $type[0] = 'printer-class';
            $value_parsed -= 1;
        }
        if ($value_parsed % 4 == 2) {
            $type[1] = 'remote-destination';
            $value_parsed -= 2;
        }
        if ($value_parsed % 8 == 4) {
            $type[2] = 'print-black';
            $value_parsed -= 4;
        }
        if ($value_parsed % 16 == 8) {
            $type[3] = 'print-color';
            $value_parsed -= 8;
        }
        if ($value_parsed % 32 == 16) {
            $type[4] = 'hardware-print-on-both-sides';
            $value_parsed -= 16;
        }
        if ($value_parsed % 64 == 32) {
            $type[5] = 'hardware-staple-output';
            $value_parsed -= 32;
        }
        if ($value_parsed % 128 == 64) {
            $type[6] = 'hardware-fast-copies';
            $value_parsed -= 64;
        }
        if ($value_parsed % 256 == 128) {
            $type[7] = 'hardware-fast-copy-collation';
            $value_parsed -= 128;
        }
        if ($value_parsed % 512 == 256) {
            $type[8] = 'punch-output';
            $value_parsed -= 256;
        }
        if ($value_parsed % 1024 == 512) {
            $type[9] = 'cover-output';
            $value_parsed -= 512;
        }
        if ($value_parsed % 2048 == 1024) {
            $type[10] = 'bind-output';
            $value_parsed -= 1024;
        }
        if ($value_parsed % 4096 == 2048) {
            $type[11] = 'sort-output';
            $value_parsed -= 2048;
        }
        if ($value_parsed % 8192 == 4096) {
            $type[12] = 'handle-media-up-to-US-Legal-A4';
            $value_parsed -= 4096;
        }
        if ($value_parsed % 16384 == 8192) {
            $type[13] = 'handle-media-between-US-Legal-A4-and-ISO_C-A2';
            $value_parsed -= 8192;
        }
        if ($value_parsed % 32768 == 16384) {
            $type[14] = 'handle-media-larger-than-ISO_C-A2';
            $value_parsed -= 16384;
        }
        if ($value_parsed % 65536 == 32768) {
            $type[15] = 'handle-user-defined-media-sizes';
            $value_parsed -= 32768;
        }
        if ($value_parsed % 131072 == 65536) {
            $type[16] = 'implicit-server-generated-class';
            $value_parsed -= 65536;
        }
        if ($value_parsed % 262144 == 131072) {
            $type[17] = 'network-default-printer';
            $value_parsed -= 131072;
        }
        if ($value_parsed % 524288 == 262144) {
            $type[18] = 'fax-device';
            // $value_parsed -= 262144;
        }

        ksort($type);

        return $type;
    }
}
