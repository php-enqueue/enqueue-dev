<?php

namespace Enqueue\Psr;

class InvalidDestinationException extends Exception
{
    /**
     * @param mixed  $destination
     * @param string $class
     *
     * @throws static
     */
    public static function assertDestinationInstanceOf($destination, $class)
    {
        if (!$destination instanceof $class) {
            throw new static(sprintf(
                'The destination must be an instance of %s but got %s.',
                $class,
                is_object($destination) ? get_class($destination) : gettype($destination)
            ));
        }
    }
}
