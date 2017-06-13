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
            $deleteReplyQueue = false;
        } else {
            $replyQueue = $this->context->createTemporaryQueue();
            $message->setReplyTo($replyQueue->getQueueName());
            $deleteReplyQueue = true;
        }

        if (false == $message->getCorrelationId()) {
            $message->setCorrelationId(UUID::generate());
        }

        $this->context->createProducer()->send($destination, $message);

        $correlationId = $message->getCorrelationId();

        $receive = function() use ($replyQueue, $timeout, $correlationId) {

            $endTime = time() + ((int) ($timeout / 1000));
            $consumer = $this->context->createConsumer($replyQueue);

            do {
                if ($message = $consumer->receive($timeout)) {
                    if ($message->getCorrelationId() === $correlationId) {
                        $consumer->acknowledge($message);

                        return $message;
                    }

                    $consumer->reject($message, true);
                }
            } while (time() < $endTime);

            throw TimeoutException::create($timeout, $correlationId);
        };

        $finally = function(Promise $promise) use ($replyQueue) {
            if ($promise->isDeleteReplyQueue()) {
                if (false == method_exists($this->context, 'deleteQueue')) {
                    throw new \RuntimeException(sprintf('Context does not support delete queues: "%s"', get_class($this->context)));
                }

                $this->context->deleteQueue($replyQueue);
            }
        };

        $promise = new Promise($receive, $finally);
        $promise->setDeleteReplyQueue($deleteReplyQueue);

        return $promise;
    }
}
