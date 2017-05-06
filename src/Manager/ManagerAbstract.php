<?php

namespace Smalot\Cups\Manager;

use Http\Client\HttpClient;
use Smalot\Cups\Builder\Builder;

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
     * @var string
     */
    protected $version;

    /**
     * ManagerAbstract constructor.
     *
     * @param \Smalot\Cups\Builder\Builder $builder
     * @param \Http\Client\HttpClient $client
     */
    public function __construct(Builder $builder, HttpClient $client)
    {
        $this->client = $client;
        $this->builder = $builder;
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
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
