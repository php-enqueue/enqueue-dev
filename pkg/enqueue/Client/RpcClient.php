<?php
namespace Enqueue\Client;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Rpc\Promise;
use Enqueue\Util\UUID;

class RpcClient
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @param DriverInterface $driver
     * @param ProducerInterface $producer
     * @param PsrContext $context
     */
    public function __construct(DriverInterface $driver, ProducerInterface $producer, PsrContext $context)
    {
        $this->driver = $driver;
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

        $transportMessage = $this->driver->createTransportMessage($message);
        if ($transportMessage->getReplyTo()) {
            $replyQueue = $this->context->createQueue($transportMessage->getReplyTo());
        } else {
            $replyQueue = $this->context->createTemporaryQueue();
            $transportMessage->setReplyTo($replyQueue->getQueueName());
        }

        if (false == $transportMessage->getCorrelationId()) {
            $transportMessage->setCorrelationId(UUID::generate());
        }

        $message = $this->driver->createClientMessage($transportMessage);

        $this->producer->send($topic, $message);

        return new Promise(
            $this->context->createConsumer($replyQueue),
            $transportMessage->getCorrelationId(),
            $timeout
        );
    }
}
