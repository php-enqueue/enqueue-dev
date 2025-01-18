<?php

namespace Enqueue\Tests\Util\Fixtures;

class JsonSerializableClass implements \JsonSerializable
{
    public $keyPublic = 'public';

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'key' => 'value',
        ];
    }
}
