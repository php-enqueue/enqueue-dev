<?php

namespace Enqueue\Psr;

interface PsrProducer
{
    /**
     * @param PsrDestination $destination
     * @param PsrMessage     $message
     *
     * @throws Exception                   if the provider fails to send the message due to some internal error
     * @throws InvalidDestinationException if a client uses this method with an invalid destination
     * @throws InvalidMessageException     if an invalid message is specified
     */
    public function send(PsrDestination $destination, PsrMessage $message);
}
