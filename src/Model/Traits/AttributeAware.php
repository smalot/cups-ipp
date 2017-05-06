<?php

namespace Smalot\Cups\Model\Traits;

/**
 * Trait AttributeAware
 *
 * @package Smalot\Cups\Model\Traits
 */
trait AttributeAware
{

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        foreach ($attributes as $name => $values) {
            $this->setAttribute($name, $values);
        }
    }

    /**
     * @param string $name
     * @param mixed $values
     */
    public function setAttribute($name, $values)
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $this->attributes[$name] = $values;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addAttribute($name, $value)
    {
        $this->attributes[$name][] = $value;
    }

    /**
     * @param string $name
     */
    public function removeAttribute($name)
    {
        unset($this->attributes[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute($name)
    {
        return isset($this->attributes[$name]);
    }
}
