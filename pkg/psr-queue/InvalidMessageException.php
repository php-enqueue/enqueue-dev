<?php
namespace Enqueue\Psr;

class InvalidMessageException extends Exception
{
    /**
     * @param Message $message
     * @param string  $class
     *
     * @throws static
     */
    public static function assertMessageInstanceOf(Message $message, $class)
    {
        if (!$message instanceof $class) {
            throw new static(sprintf(
                'The message must be an instance of %s but it is %s.',
                $class,
                get_class($message)
            ));
        }
    }
}
