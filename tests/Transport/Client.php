<?php

namespace Smalot\Cups\Tests\Units\Transport;

use Psr\Http\Message\RequestInterface;
use Smalot\Cups\Transport\Client as BaseClient;

/**
 * Class Client
 *
 * @package Smalot\Cups\Tests\Units\Transport
 */
class Client extends BaseClient
{

    /**
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $lastRequest;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $lastResponse;

    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request)
    {
        $this->lastRequest = $request;
        $this->lastResponse = parent::sendRequest($request);

        return $this->lastResponse;
    }

    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
