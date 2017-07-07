<?php

namespace Enqueue\Stomp;

use Interop\Queue\CompletionListener;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Stomp\Client;
use Stomp\Transport\Message as StompLibMessage;

class StompProducer implements PsrProducer
{
    /**
     * @var CompletionListener
     */
    private $completionListener;

    /**
     * @var float
     */
    private $deliveryDelay = PsrMessage::DEFAULT_DELIVERY_DELAY;

    /**
     * @var Client
     */
    private $stomp;

    /**
     * @param Client $stomp
     */
    public function __construct(Client $stomp)
    {
        $this->stomp = $stomp;
    }

    /**
     * {@inheritdoc}
     *
     * @param StompDestination $destination
     * @param StompMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, StompDestination::class);

        InvalidMessageException::assertMessageInstanceOf($message, StompMessage::class);

        $headers = array_merge($message->getHeaders(), $destination->getHeaders());
        $headers = StompHeadersEncoder::encode($headers, $message->getProperties());

        $stompMessage = new StompLibMessage($message->getBody(), $headers);

        $this->stomp->send($destination->getQueueName(), $stompMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function setCompletionListener(CompletionListener $listener = null)
    {
        $this->completionListener = $listener;
    }

    /**
     * @return CompletionListener|null
     */
    public function getCompletionListener()
    {
        return $this->completionListener;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return $this->deliveryDelay;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        $this->deliveryDelay = $deliveryDelay;
    }
}
