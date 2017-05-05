<?php

namespace Smalot\Cups\Manager;

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
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->version = chr(0x01).chr(0x01);
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
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
