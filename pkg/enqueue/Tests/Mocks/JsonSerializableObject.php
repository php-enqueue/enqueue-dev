<?php

namespace Enqueue\Tests\Mocks;

class JsonSerializableObject implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'fooVal'];
    }
}
