<?php

namespace Enqueue\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class StompDriver implements DriverInterface
{
    /**
     * @var StompContext
     */
    private $context;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var QueueMetaRegistry
     */
    private $queueMetaRegistry;

    public function __construct(StompContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
        $this->context = $context;
        $this->config = $config;
        $this->queueMetaRegistry = $queueMetaRegistry;
    }

    public function sendToRouter(Message $message): void
    {
        if (false == $message->getProperty(Config::PARAMETER_TOPIC_NAME)) {
            throw new \LogicException('Topic name parameter is required but is not set');
        }

        $topic = $this->createRouterTopic();
        $transportMessage = $this->createTransportMessage($message);

        $this->context->createProducer()->send($topic, $transportMessage);
    }

    public function sendToProcessor(Message $message): void
    {
        if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
            throw new \LogicException('Processor name parameter is required but is not set');
        }

        if (false == $queueName = $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
            throw new \LogicException('Queue name parameter is required but is not set');
        }

        $transportMessage = $this->createTransportMessage($message);
        $destination = $this->createQueue($queueName);

        $this->context->createProducer()->send($destination, $transportMessage);
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
        $headers = $message->getHeaders();
        $headers['content-type'] = $message->getContentType();

        $transportMessage = $this->context->createMessage();
        $transportMessage->setHeaders($headers);
        $transportMessage->setPersistent(true);
        $transportMessage->setBody($message->getBody());
        $transportMessage->setProperties($message->getProperties());

        if ($message->getMessageId()) {
            $transportMessage->setMessageId($message->getMessageId());
        }

        if ($message->getTimestamp()) {
            $transportMessage->setTimestamp($message->getTimestamp());
        }

        if ($message->getReplyTo()) {
            $transportMessage->setReplyTo($message->getReplyTo());
        }

        if ($message->getCorrelationId()) {
            $transportMessage->setCorrelationId($message->getCorrelationId());
        }

        return $transportMessage;
    }

    /**
     * @param StompMessage $message
     */
    public function createClientMessage(PsrMessage $message): Message
    {
        $clientMessage = new Message();

        $headers = $message->getHeaders();
        unset(
            $headers['content-type'],
            $headers['message_id'],
            $headers['timestamp'],
            $headers['reply-to'],
            $headers['correlation_id']
        );

        $clientMessage->setHeaders($headers);
        $clientMessage->setBody($message->getBody());
        $clientMessage->setProperties($message->getProperties());

        $clientMessage->setContentType($message->getHeader('content-type'));

        $clientMessage->setMessageId($message->getMessageId());
        $clientMessage->setTimestamp($message->getTimestamp());
        $clientMessage->setReplyTo($message->getReplyTo());
        $clientMessage->setCorrelationId($message->getCorrelationId());

        return $clientMessage;
    }

    /**
     * @return StompDestination
     */
    public function createQueue(string $queueName): PsrQueue
    {
        $transportName = $this->queueMetaRegistry->getQueueMeta($queueName)->getTransportName();

        $queue = $this->context->createQueue($transportName);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
        $queue->setExclusive(false);

        return $queue;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    private function createRouterTopic(): StompDestination
    {
        $topic = $this->context->createTopic(
            $this->config->createTransportRouterTopicName($this->config->getRouterTopicName())
        );
        $topic->setDurable(true);
        $topic->setAutoDelete(false);

        return $topic;
    }
}
