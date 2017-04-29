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
     * @var mixed
     */
    protected $body;

    /**
     * Response constructor.
     *
     * @param string $ippVersion
     * @param string $statusCode
     * @param string $requestId
     * @param mixed $body
     */
    public function __construct($ippVersion, $statusCode, $requestId, $body)
    {
        $this->ippVersion = $ippVersion;
        $this->statusCode = $statusCode;
        $this->requestId = $requestId;
        $this->body = $body;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public static function parseResponse(ResponseInterface $response)
    {
        $parser = new ResponseParser($response);

        $ippVersion = $parser->getIppVersion();
        $statusCode = $parser->getStatusCode();
        $requestId = $parser->getRequestId();
        $body = $parser->getBody();

        return new self($ippVersion, $statusCode, $requestId, $body);
    }
}
