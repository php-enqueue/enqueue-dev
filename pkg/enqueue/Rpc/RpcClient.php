<?php
namespace Enqueue\Rpc;

use Enqueue\Psr\Context;
use Enqueue\Psr\Destination;
use Enqueue\Psr\Message;
use Enqueue\Util\UUID;

class RpcClient
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
     * @param Destination $destination
     * @param Message     $message
     * @param $timeout
     *
     * @return Message
     */
    public function call(Destination $destination, Message $message, $timeout)
    {
        return $this->callAsync($destination, $message, $timeout)->getMessage();
    }

    /**
     * @param Destination $destination
     * @param Message     $message
     * @param $timeout
     *
     * @return Promise
     */
    public function callAsync(Destination $destination, Message $message, $timeout)
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
