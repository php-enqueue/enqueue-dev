<?php

namespace Enqueue\Gps\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsMessage;
use Enqueue\Gps\GpsQueue;
use Enqueue\Gps\GpsTopic;
use Interop\Queue\PsrMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GpsDriver implements DriverInterface
{
    /**
     * @var GpsContext
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

    /**
     * @param GpsContext        $context
     * @param Config            $config
     * @param QueueMetaRegistry $queueMetaRegistry
     */
    public function __construct(GpsContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
        $this->context = $context;
        $this->config = $config;
        $this->queueMetaRegistry = $queueMetaRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function sendToRouter(Message $message)
    {
        if (false == $message->getProperty(Config::PARAMETER_TOPIC_NAME)) {
            throw new \LogicException('Topic name parameter is required but is not set');
        }

        $topic = $this->createRouterTopic();
        $transportMessage = $this->createTransportMessage($message);

        $this->context->createProducer()->send($topic, $transportMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function sendToProcessor(Message $message)
    {
        if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
            throw new \LogicException('Processor name parameter is required but is not set');
        }

        if (false == $queueName = $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
            throw new \LogicException('Queue name parameter is required but is not set');
        }

        $transportMessage = $this->createTransportMessage($message);
        $destination = $this->context->createTopic(
            $this->queueMetaRegistry->getQueueMeta($queueName)->getTransportName())
        ;

        $this->context->createProducer()->send($destination, $transportMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function setupBroker(LoggerInterface $logger = null)
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[GpsDriver] '.$text, ...$args));
        };

        // setup router
        $routerTopic = $this->createRouterTopic();
        $routerQueue = $this->createQueue($this->config->getRouterQueueName());

        $log('Subscribe router topic to queue: %s -> %s', $routerTopic->getTopicName(), $routerQueue->getQueueName());
        $this->context->subscribe($routerTopic, $routerQueue);

        // setup queues
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
            $topic = $this->context->createTopic($meta->getTransportName());
            $queue = $this->context->createQueue($meta->getTransportName());

            $log('Subscribe processor topic to queue: %s -> %s', $topic->getTopicName(), $queue->getQueueName());
            $this->context->subscribe($topic, $queue);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return GpsQueue
     */
    public function createQueue($queueName)
    {
        $transportName = $this->queueMetaRegistry->getQueueMeta($queueName)->getTransportName();

        return $this->context->createQueue($transportName);
    }

    /**
     * {@inheritdoc}
     *
     * @return GpsMessage
     */
    public function createTransportMessage(Message $message)
    {
        $headers = $message->getHeaders();
        $properties = $message->getProperties();

        $transportMessage = $this->context->createMessage();
        $transportMessage->setBody($message->getBody());
        $transportMessage->setHeaders($headers);
        $transportMessage->setProperties($properties);
        $transportMessage->setMessageId($message->getMessageId());
        $transportMessage->setTimestamp($message->getTimestamp());
        $transportMessage->setReplyTo($message->getReplyTo());
        $transportMessage->setCorrelationId($message->getCorrelationId());

        return $transportMessage;
    }

    /**
     * @param GpsMessage $message
     *
     * {@inheritdoc}
     */
    public function createClientMessage(PsrMessage $message)
    {
        $clientMessage = new Message();

        $clientMessage->setBody($message->getBody());
        $clientMessage->setHeaders($message->getHeaders());
        $clientMessage->setProperties($message->getProperties());
        $clientMessage->setMessageId($message->getMessageId());
        $clientMessage->setTimestamp($message->getTimestamp());
        $clientMessage->setReplyTo($message->getReplyTo());
        $clientMessage->setCorrelationId($message->getCorrelationId());

        return $clientMessage;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return GpsTopic
     */
    private function createRouterTopic()
    {
        $topic = $this->context->createTopic(
            $this->config->createTransportRouterTopicName($this->config->getRouterTopicName())
        );

        return $topic;
    }
}
