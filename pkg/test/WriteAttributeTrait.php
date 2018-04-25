<?php

namespace Enqueue\Test;

trait WriteAttributeTrait
{
    /**
     * @param object $object
     * @param string $attribute
     * @param mixed  $value
     */
    public function writeAttribute($object, $attribute, $value)
    {
        $refProperty = new \ReflectionProperty(get_class($object), $attribute);
        $refProperty->setAccessible(true);
        $refProperty->setValue($object, $value);
        $refProperty->setAccessible(false);
    }
}
