<?php

namespace Enqueue\Client;

use Interop\Queue\Destination;
use Interop\Queue\Message as TransportMessage;

final class DriverSendResult
{
    /**
     * @var Destination
     */
    private $transportDestination;

    /**
     * @var TransportMessage
     */
    private $transportMessage;

    public function __construct(Destination $transportDestination, TransportMessage $transportMessage)
    {
        $this->transportDestination = $transportDestination;
        $this->transportMessage = $transportMessage;
    }

    public function getTransportDestination(): Destination
    {
        return $this->transportDestination;
    }

    public function getTransportMessage(): TransportMessage
    {
        return $this->transportMessage;
    }
}
