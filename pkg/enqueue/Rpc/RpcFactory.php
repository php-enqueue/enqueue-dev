<?php

namespace Enqueue\Rpc;

use Interop\Queue\Context;

class RpcFactory
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param string $replyTo
     * @param string $correlationId
     * @param int    $timeout
     *
     * @return Promise
     */
    public function createPromise($replyTo, $correlationId, $timeout)
    {
        $replyQueue = $this->context->createQueue($replyTo);

        $receive = function (Promise $promise, $promiseTimeout) use ($replyQueue, $timeout, $correlationId) {
            $runTimeout = $promiseTimeout ?: $timeout;
            $endTime = time() + ((int) ($runTimeout / 1000));
            $consumer = $this->context->createConsumer($replyQueue);

            do {
                if ($message = $consumer->receive($runTimeout)) {
                    if ($message->getCorrelationId() === $correlationId) {
                        $consumer->acknowledge($message);

                        return $message;
                    }

                    $consumer->reject($message, true);
                }
            } while (time() < $endTime);

            throw TimeoutException::create($runTimeout, $correlationId);
        };

        $receiveNoWait = function () use ($replyQueue, $correlationId) {
            static $consumer;

            if (null === $consumer) {
                $consumer = $this->context->createConsumer($replyQueue);
            }

            if ($message = $consumer->receiveNoWait()) {
                if ($message->getCorrelationId() === $correlationId) {
                    $consumer->acknowledge($message);

                    return $message;
                }

                $consumer->reject($message, true);
            }
        };

        $finally = function (Promise $promise) use ($replyQueue) {
            if ($promise->isDeleteReplyQueue() && method_exists($this->context, 'deleteQueue')) {
                $this->context->deleteQueue($replyQueue);
            }
        };

        return new Promise($receive, $receiveNoWait, $finally);
    }

    /**
     * @return string
     */
    public function createReplyTo()
    {
        return $this->context->createTemporaryQueue()->getQueueName();
    }
}
