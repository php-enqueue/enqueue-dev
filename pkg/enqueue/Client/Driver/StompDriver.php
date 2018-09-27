<?php

namespace Enqueue\Client\Driver;

use Enqueue\Client\Message;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method StompContext getContext
 */
class StompDriver extends GenericDriver
{
    public function __construct(StompContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $logger->debug('[StompDriver] Stomp protocol does not support broker configuration');
    }

    /**
     * @return StompMessage
     */
    public function createTransportMessage(Message $message): PsrMessage
    {
        /** @var StompMessage $transportMessage */
        $transportMessage = parent::createTransportMessage($message);
        $transportMessage->setPersistent(true);

        return $transportMessage;
    }

    /**
     * @return StompDestination
     */
    protected function doCreateQueue(string $transportQueueName): PsrQueue
    {
        /** @var StompDestination $queue */
        $queue = parent::doCreateQueue($transportQueueName);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
        $queue->setExclusive(false);

        return $queue;
    }

    /**
     * @return StompDestination
     */
    protected function createRouterTopic(): PsrDestination
    {
        /** @var StompDestination $topic */
        $topic = $this->doCreateTopic(
            $this->createTransportRouterTopicName($this->getConfig()->getRouterTopicName(), true)
        );
        $topic->setDurable(true);
        $topic->setAutoDelete(false);

        return $topic;
    }
}
