<?php

namespace Enqueue\Gearman;

use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProducer;

class GearmanProducer implements PsrProducer
{
    /**
     * @var \GearmanClient
     */
    private $client;

    /**
     * @param \GearmanClient $client
     */
    public function __construct(\GearmanClient $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     *
     * @param GearmanDestination $destination
     * @param GearmanMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GearmanDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, GearmanMessage::class);

        $this->client->doBackground($destination->getName(), json_encode($message));

        $code = $this->client->returnCode();
        if (\GEARMAN_SUCCESS !== $code) {
            throw new \GearmanException(sprintf('The return code is not %s (GEARMAN_SUCCESS) but %s', \GEARMAN_SUCCESS, $code));
        }
    }
}
