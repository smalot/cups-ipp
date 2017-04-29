<?php

namespace Smalot\Cups\Connector;

/**
 * Trait LanguageAware
 *
 * @package Smalot\Cups\Connector
 */
trait LanguageAware
{
    protected $language;

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     *
     * @return LanguageAware
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }
}
