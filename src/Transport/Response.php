<?php

namespace Smalot\Cups\Transport;

use Psr\Http\Message\ResponseInterface;

/**
 * Class Response
 *
 * @package Smalot\Cups\Transport
 */
class Response
{

    /**
     * @var string
     */
    protected $ippVersion;

    /**
     * @var string
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $requestId;

    /**
     * @var array
     */
    protected $body;

    /**
     * @var array
     */
    protected $values;

    /**
     * Response constructor.
     *
     * @param string $ippVersion
     * @param string $statusCode
     * @param string $requestId
     * @param array $body
     */
    public function __construct($ippVersion, $statusCode, $requestId, $body)
    {
        $this->ippVersion = $ippVersion;
        $this->statusCode = $statusCode;
        $this->requestId = $requestId;
        $this->body = $body;

        $this->values = $this->prepareValues($body);
    }

    /**
     * @return string
     */
    public function getIppVersion()
    {
        return $this->ippVersion;
    }

    /**
     * @return string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        if (!empty($this->values['operation-attributes'][0]['status-message'][0])) {
            return $this->values['operation-attributes'][0]['status-message'][0];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string|false
     */
    public function getCharset()
    {
        if (!empty($this->values['operation-attributes'][0]['attributes-charset'][0])) {
            return $this->values['operation-attributes'][0]['attributes-charset'][0];
        }

        return false;
    }

    /**
     * @return string|false
     */
    public function getLanguage()
    {
        if (!empty($this->values['operation-attributes'][0]['attributes-natural-language'][0])) {
            return $this->values['operation-attributes'][0]['attributes-natural-language'][0];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        $values = $this->values;

        unset($values['operation-attributes']);
        unset($values['end-of-attributes']);

        return $values;
    }

    /**
     * @param array $list
     *
     * @return array
     */
    protected function prepareValues($list)
    {
        unset($list['attributes']);

        $values = [];
        $name = '';

        foreach ($list as $item) {
            if (isset($item['attributes'])) {
                $name = $item['attributes'];
                unset($item['attributes']);
                $values[$name][] = $this->prepareValues($item);
                continue;
            } elseif (!empty($item['name'])) {
                $name = $item['name'];
            }

            $values[$name][] = $item['value'];
        }

        return $values;
    }
}
