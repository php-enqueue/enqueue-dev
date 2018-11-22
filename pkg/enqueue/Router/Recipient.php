<?php

namespace Enqueue\Router;

use Interop\Queue\Destination;
use Interop\Queue\Message as InteropMessage;

class Recipient
{
    /**
     * @var Destination
     */
    private $destination;

    /**
     * @var InteropMessage
     */
    private $message;

    /**
     * @param Destination    $destination
     * @param InteropMessage $message
     */
    public function __construct(Destination $destination, InteropMessage $message)
    {
        $this->destination = $destination;
        $this->message = $message;
    }

    /**
     * @return Destination
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return InteropMessage
     */
    public function getMessage()
    {
        return $this->message;
    }
}
