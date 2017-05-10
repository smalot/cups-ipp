<?php

namespace Smalot\Cups\Manager;

use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Transport\ResponseParser;

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
     * @var \Http\Client\HttpClient
     */
    protected $client;

    /**
     * @var \Smalot\Cups\Builder\Builder
     */
    protected $builder;

    /**
     * @var \Smalot\Cups\Transport\ResponseParser
     */
    protected $responseParser;

    /**
     * @var string
     */
    protected $version;

    /**
     * ManagerAbstract constructor.
     *
     * @param \Smalot\Cups\Builder\Builder $builder
     * @param \Http\Client\HttpClient $client
     * @param \Smalot\Cups\Transport\ResponseParser $responseParser
     */
    public function __construct(Builder $builder, HttpClient $client, ResponseParser $responseParser)
    {
        $this->client = $client;
        $this->builder = $builder;
        $this->responseParser = $responseParser;
        $this->version = chr(0x01).chr(0x01);

        $this->setCharset('us-ascii');
        $this->setLanguage('en-us');
        $this->setOperationId(0);
        $this->setUsername('');
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param bool $emptyIfMissing
     *
     * @return string
     */
    public function buildProperty($name, $value, $emptyIfMissing = false)
    {
        return $this->builder->buildProperty($name, $value, $emptyIfMissing);
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    public function buildProperties($properties = [])
    {
        return $this->builder->buildProperties($properties);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Smalot\Cups\Transport\Response
     */
    public function parseResponse(ResponseInterface $response)
    {
        return $this->responseParser->parse($response);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
