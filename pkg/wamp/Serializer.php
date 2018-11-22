<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

interface Serializer
{
    public function toString(WampMessage $message): string;

    public function toMessage(string $string): WampMessage;
}
