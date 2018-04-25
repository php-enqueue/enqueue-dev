<?php

namespace Enqueue\Tests\Util\Fixtures;

class JsonSerializableClass implements \JsonSerializable
{
    public $keyPublic = 'public';

    public function jsonSerialize()
    {
        return [
            'key' => 'value',
        ];
    }
}
