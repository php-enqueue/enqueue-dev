<?php

namespace Enqueue\Consumption\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param string $class
     *
     * @throws static
     */
    public static function assertInstanceOf($argument, $class)
    {
        if (false == $argument instanceof $class) {
            throw new self(sprintf('The argument must be an instance of %s but got %s.', $class, is_object($argument) ? $argument::class : gettype($argument)));
        }
    }
}
