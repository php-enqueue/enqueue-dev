<?php

namespace Enqueue\Dsn;

final class InvalidQueryParameterTypeException extends \LogicException
{
    public static function create(string $name, string $expectedType): self
    {
        return new static(sprintf('The query parameter "%s" has invalid type. It must be "%s"', $name, $expectedType));
    }
}
