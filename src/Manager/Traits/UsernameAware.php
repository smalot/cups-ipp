<?php

namespace Smalot\Cups\Manager\Traits;

/**
 * Trait UsernameAware
 *
 * @package Smalot\Cups\Manager\Traits
 */
trait UsernameAware
{

    /**
     * @var string
     */
    protected $username;

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return UsernameAware
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    protected function buildUsername()
    {
        $metaUsername = '';

        if ($this->username) {
            $metaUsername = chr(0x42) // keyword type || value-tag
              .chr(0x00).chr(0x14) // name-length
              .'requesting-user-name'
              .$this->builder->formatStringLength($this->username) // value-length
              .$this->username;
        }

        return $metaUsername;
    }
}
