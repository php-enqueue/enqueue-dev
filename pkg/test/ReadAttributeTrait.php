<?php

namespace Enqueue\Test;

trait ReadAttributeTrait
{
    public function readAttribute(object $object, string $attribute)
    {
        $refProperty = new \ReflectionProperty(get_class($object), $attribute);
        $refProperty->setAccessible(true);
        $value = $refProperty->getValue($object);
        $refProperty->setAccessible(false);
        return $value;
    }
}
