<?php

namespace Enqueue\Null;

use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;

class NullProducer implements PsrProducer
{
    /**
     * {@inheritdoc}
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
    }
}
