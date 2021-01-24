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
