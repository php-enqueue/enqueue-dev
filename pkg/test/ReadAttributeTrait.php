<?php

namespace Enqueue\Test;

use ReflectionProperty;

trait ReadAttributeTrait
{
    public function readAttribute(object $object, string $attribute)
    {
        $refProperty = $this->getClassAttribute($object, $attribute);
        $refProperty->setAccessible(true);
        $value = $refProperty->getValue($object);
        $refProperty->setAccessible(false);

        return $value;
    }

    private function getClassAttribute(
        object $object,
        string $attribute,
        ?string $class = null
    ): ReflectionProperty {
        if ($class === null) {
            $class = get_class($object);
        }

        try {
            return new ReflectionProperty($class, $attribute);
        } catch (\ReflectionException $exception) {
            $parentClass = get_parent_class($object);
            if ($parentClass === false) {
                throw $exception;
            }

            return $this->getClassAttribute($object, $attribute, $parentClass);
        }
    }

    private function assertAttributeSame($expected, string $attribute, object $object): void
    {
        static::assertSame($expected, $this->readAttribute($object, $attribute));
    }

    private function assertAttributeEquals($expected, string $attribute, object $object): void
    {
        static::assertEquals($expected, $this->readAttribute($object, $attribute));
    }

    private function assertAttributeInstanceOf(string $expected, string $attribute, object $object): void
    {
        static::assertInstanceOf($expected, $this->readAttribute($object, $attribute));
    }

    private function assertAttributeCount(int $count, string $attribute, object $object): void
    {
        static::assertCount($count, $this->readAttribute($object, $attribute));
    }
}
