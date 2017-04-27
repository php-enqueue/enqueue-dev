<?php

namespace Enqueue\Null;

use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProducer;

class NullProducer implements PsrProducer
{
    /**
     * {@inheritdoc}
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
    }
}
