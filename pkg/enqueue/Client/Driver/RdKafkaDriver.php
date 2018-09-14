<?php

namespace Enqueue\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\RdKafkaTopic;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RdKafkaDriver implements DriverInterface
{
    /**
     * @var RdKafkaContext
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

    public function __construct(RdKafkaContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
        $this->context = $context;
        $this->config = $config;
        $this->queueMetaRegistry = $queueMetaRegistry;
    }

    /**
     * @return RdKafkaMessage
     */
    public function createTransportMessage(Message $message): PsrMessage
    {
        $headers = $message->getHeaders();
        $headers['content_type'] = $message->getContentType();

        $transportMessage = $this->context->createMessage();
        $transportMessage->setBody($message->getBody());
        $transportMessage->setHeaders($headers);
        $transportMessage->setProperties($message->getProperties());
        $transportMessage->setMessageId($message->getMessageId());
        $transportMessage->setTimestamp($message->getTimestamp());
        $transportMessage->setReplyTo($message->getReplyTo());
        $transportMessage->setCorrelationId($message->getCorrelationId());

        return $transportMessage;
    }

    public function createClientMessage(PsrMessage $message): Message
    {
        $clientMessage = new Message();
        $clientMessage->setBody($message->getBody());
        $clientMessage->setHeaders($message->getHeaders());
        $clientMessage->setProperties($message->getProperties());

        $clientMessage->setContentType($message->getHeader('content_type'));

        $clientMessage->setTimestamp($message->getTimestamp());
        $clientMessage->setMessageId($message->getMessageId());
        $clientMessage->setReplyTo($message->getReplyTo());
        $clientMessage->setCorrelationId($message->getCorrelationId());

        return $clientMessage;
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

    /**
     * @return RdKafkaTopic
     */
    public function createQueue(string $queueName): PsrQueue
    {
        $transportName = $this->queueMetaRegistry->getQueueMeta($queueName)->getTransportName();

        return $this->context->createQueue($transportName);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $logger->debug('[RdKafkaDriver] setup broker');
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[RdKafkaDriver] '.$text, ...$args));
        };

        // setup router
        $routerQueue = $this->createQueue($this->config->getRouterQueueName());
        $log('Create router queue: %s', $routerQueue->getQueueName());
        $this->context->createConsumer($routerQueue);

        // setup queues
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
            $queue = $this->createQueue($meta->getClientName());
            $log('Create processor queue: %s', $queue->getQueueName());
            $this->context->createConsumer($queue);
        }
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    private function createRouterTopic(): RdKafkaTopic
    {
        $topic = $this->context->createTopic(
            $this->config->createTransportRouterTopicName($this->config->getRouterTopicName())
        );

        return $topic;
    }
}
