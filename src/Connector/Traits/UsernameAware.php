<?php

namespace Smalot\Cups\Connector\Traits;

/**
 * Trait UsernameAware
 *
 * @package Smalot\Cups\Connector\Traits
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
     * @param string $username
     *
     * @return string
     */
    protected function buildUsername($username = 'PHP-SERVER')
    {
        if ($username != 'PHP-SERVER') {
            $metaUsername = chr(0x42) // keyword type || value-tag
              .chr(0x00).chr(0x14) // name-length
              .'requesting-user-name'
              .$this->getStringLength($username) // value-length
              .$username;
        } else {
            $metaUsername = '';
        }

        return $metaUsername;
    }
}
