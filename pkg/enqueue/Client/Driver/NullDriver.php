<?php

namespace Enqueue\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NullDriver implements DriverInterface
{
    /**
     * @var NullContext
     */
    protected $context;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var QueueMetaRegistry
     */
    private $queueMetaRegistry;

    public function __construct(NullContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
        $this->context = $context;
        $this->config = $config;
        $this->queueMetaRegistry = $queueMetaRegistry;
    }

    /**
     * @return NullMessage
     */
    public function createTransportMessage(Message $message): PsrMessage
    {
        $headers = $message->getHeaders();
        $headers['content_type'] = $message->getContentType();
        $headers['expiration'] = $message->getExpire();
        $headers['delay'] = $message->getDelay();
        $headers['priority'] = $message->getPriority();

        $transportMessage = $this->context->createMessage();
        $transportMessage->setBody($message->getBody());
        $transportMessage->setHeaders($headers);
        $transportMessage->setProperties($message->getProperties());
        $transportMessage->setTimestamp($message->getTimestamp());
        $transportMessage->setMessageId($message->getMessageId());
        $transportMessage->setReplyTo($message->getReplyTo());
        $transportMessage->setCorrelationId($message->getCorrelationId());

        return $transportMessage;
    }

    /**
     * @param NullMessage $message
     */
    public function createClientMessage(PsrMessage $message): Message
    {
        $clientMessage = new Message();
        $clientMessage->setBody($message->getBody());
        $clientMessage->setHeaders($message->getHeaders());
        $clientMessage->setProperties($message->getProperties());
        $clientMessage->setTimestamp($message->getTimestamp());
        $clientMessage->setMessageId($message->getMessageId());
        $clientMessage->setReplyTo($message->getReplyTo());
        $clientMessage->setCorrelationId($message->getCorrelationId());

        if ($contentType = $message->getHeader('content_type')) {
            $clientMessage->setContentType($contentType);
        }

        if ($expiration = $message->getHeader('expiration')) {
            $clientMessage->setExpire($expiration);
        }

        if ($delay = $message->getHeader('delay')) {
            $clientMessage->setDelay($delay);
        }

        if ($priority = $message->getHeader('priority')) {
            $clientMessage->setPriority($priority);
        }

        return $clientMessage;
    }

    /**
     * @return NullQueue
     */
    public function createQueue(string $queueName): PsrQueue
    {
        $transportName = $this->queueMetaRegistry->getQueueMeta($queueName)->getTransportName();

        return $this->context->createQueue($transportName);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function sendToRouter(Message $message): void
    {
        $transportMessage = $this->createTransportMessage($message);
        $topic = $this->context->createTopic(
            $this->config->createTransportRouterTopicName(
                $this->config->getRouterTopicName()
            )
        );

        $this->context->createProducer()->send($topic, $transportMessage);
    }

    public function sendToProcessor(Message $message): void
    {
        $transportMessage = $this->createTransportMessage($message);
        $queue = $this->context->createQueue(
            $this->config->createTransportQueueName(
                $this->config->getRouterQueueName()
            )
        );

        $this->context->createProducer()->send($queue, $transportMessage);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger ?: new NullLogger();
        $logger->debug('[NullDriver] setup broker');
    }
}
