<?php

namespace Smalot\Cups\Connector;

/**
 * Trait CharsetAware
 *
 * @package Smalot\Cups\Connector
 */
trait CharsetAware
{
    protected $charset;

    /**
     * @return mixed
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @param mixed $charset
     *
     * @return CharsetAware
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }
}
