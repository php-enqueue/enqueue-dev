<?php

namespace Enqueue\Client;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\TimeoutException;
use Enqueue\Util\UUID;

class RpcClient
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @param ProducerInterface $producer
     * @param PsrContext        $context
     */
    public function __construct(ProducerInterface $producer, PsrContext $context)
    {
        $this->context = $context;
        $this->producer = $producer;
    }

    /**
     * @param string               $topic
     * @param string|array|Message $message
     * @param int                  $timeout
     *
     * @return PsrMessage
     */
    public function call($topic, $message, $timeout)
    {
        return $this->callAsync($topic, $message, $timeout)->receive();
    }

    /**
     * @param string               $topic
     * @param string|array|Message $message $message
     * @param int                  $timeout
     *
     * @return Promise
     */
    public function callAsync($topic, $message, $timeout)
    {
        if ($timeout < 1) {
            throw new \InvalidArgumentException(sprintf('Timeout must be positive not zero integer. Got %s', $timeout));
        }

        if (false == $message instanceof Message) {
            $body = $message;
            $message = new Message();
            $message->setBody($body);
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

        $this->producer->send($topic, $message);

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

        $receiveNoWait = function() use ($replyQueue, $correlationId) {

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

        $finally = function(Promise $promise) use ($replyQueue) {
            if ($promise->isDeleteReplyQueue()) {
                if (false == method_exists($this->context, 'deleteQueue')) {
                    throw new \RuntimeException(sprintf('Context does not support delete queues: "%s"', get_class($this->context)));
                }

                $this->context->deleteQueue($replyQueue);
            }
        };

        $promise = new Promise($receive, $receiveNoWait, $finally);
        $promise->setDeleteReplyQueue($deleteReplyQueue);

        return $promise;
    }
}
