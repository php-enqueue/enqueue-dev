<?php

namespace Enqueue\Tests\Mocks;

class JsonSerializableObject implements \JsonSerializable
{
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return ['foo' => 'fooVal'];
    }
}
