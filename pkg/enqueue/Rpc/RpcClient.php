<?php

namespace Enqueue\Rpc;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use Enqueue\Util\UUID;

class RpcClient
{
    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @param PsrContext $context
     */
    public function __construct(PsrContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param PsrDestination $destination
     * @param PsrMessage     $message
     * @param int            $timeout
     *
     * @throws TimeoutException if the wait timeout is reached
     *
     * @return PsrMessage
     */
    public function call(PsrDestination $destination, PsrMessage $message, $timeout)
    {
        return $this->callAsync($destination, $message, $timeout)->getMessage();
    }

    /**
     * @param PsrDestination $destination
     * @param PsrMessage     $message
     * @param int            $timeout
     *
     * @return Promise
     */
    public function callAsync(PsrDestination $destination, PsrMessage $message, $timeout)
    {
        if ($timeout < 1) {
            throw new \InvalidArgumentException(sprintf('Timeout must be positive not zero integer. Got %s', $timeout));
        }

        if ($message->getReplyTo()) {
            $replyQueue = $this->context->createQueue($message->getReplyTo());
        } else {
            $replyQueue = $this->context->createTemporaryQueue();
            $message->setReplyTo($replyQueue->getQueueName());
        }

        if (false == $message->getCorrelationId()) {
            $message->setCorrelationId(UUID::generate());
        }

        $this->context->createProducer()->send($destination, $message);

        return new Promise(
            $this->context->createConsumer($replyQueue),
            $message->getCorrelationId(),
            $timeout
        );
    }
}
