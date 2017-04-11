<?php
namespace Enqueue\Client;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Rpc\Promise;
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
     * @param PsrContext $context
     */
    public function __construct(ProducerInterface $producer, PsrContext $context)
    {
        $this->context = $context;
        $this->producer = $producer;
    }

    /**
     * @param string $topic
     * @param string|array|Message $message
     * @param int $timeout
     *
     * @return PsrMessage
     */
    public function call($topic, $message, $timeout)
    {
        return $this->callAsync($topic, $message, $timeout)->getMessage();
    }

    /**
     * @param string $topic
     * @param string|array|Message $message $message
     * @param int $timeout
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
        } else {
            $replyQueue = $this->context->createTemporaryQueue();
            $message->setReplyTo($replyQueue->getQueueName());
        }

        if (false == $message->getCorrelationId()) {
            $message->setCorrelationId(UUID::generate());
        }

        $this->producer->send($topic, $message);

        return new Promise(
            $this->context->createConsumer($replyQueue),
            $message->getCorrelationId(),
            $timeout
        );
    }
}
