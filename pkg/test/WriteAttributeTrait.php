<?php

namespace Enqueue\Test;

trait WriteAttributeTrait
{
    /**
     * @param object $object
     * @param string $attribute
     */
    public function writeAttribute($object, $attribute, $value)
    {
        $refProperty = new \ReflectionProperty($object::class, $attribute);
        $refProperty->setAccessible(true);
        $refProperty->setValue($object, $value);
        $refProperty->setAccessible(false);
    }
}
