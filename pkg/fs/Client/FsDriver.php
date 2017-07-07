<?php

namespace  Enqueue\Fs\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use Interop\Queue\PsrMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FsDriver implements DriverInterface
{
    /**
     * @var FsContext
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
     * @param FsContext         $context
     * @param Config            $config
     * @param QueueMetaRegistry $queueMetaRegistry
     */
    public function __construct(FsContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry)
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
        $destination = $this->createQueue($queueName);

        $this->context->createProducer()->send($destination, $transportMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function setupBroker(LoggerInterface $logger = null)
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[FsDriver] '.$text, ...$args));
        };

        // setup router
        $routerTopic = $this->createRouterTopic();
        $routerQueue = $this->createQueue($this->config->getRouterQueueName());

        $log('Declare router exchange "%s" file: %s', $routerTopic->getTopicName(), $routerTopic->getFileInfo());
        $this->context->declareDestination($routerTopic);

        $log('Declare router queue "%s" file: %s', $routerQueue->getQueueName(), $routerTopic->getFileInfo());
        $this->context->declareDestination($routerQueue);

        // setup queues
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
            $queue = $this->createQueue($meta->getClientName());

            $log('Declare processor queue "%s" file: %s', $queue->getQueueName(), $queue->getFileInfo());
            $this->context->declareDestination($queue);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return FsDestination
     */
    public function createQueue($queueName)
    {
        $transportName = $this->queueMetaRegistry->getQueueMeta($queueName)->getTransportName();

        return $this->context->createQueue($transportName);
    }

    /**
     * {@inheritdoc}
     *
     * @return FsMessage
     */
    public function createTransportMessage(Message $message)
    {
        $properties = $message->getProperties();

        $headers = $message->getHeaders();
        $headers['content_type'] = $message->getContentType();

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
     * @param FsMessage $message
     *
     * {@inheritdoc}
     */
    public function createClientMessage(PsrMessage $message)
    {
        $clientMessage = new Message();

        $clientMessage->setBody($message->getBody());
        $clientMessage->setHeaders($message->getHeaders());
        $clientMessage->setProperties($message->getProperties());

        $clientMessage->setContentType($message->getHeader('content_type'));
        $clientMessage->setMessageId($message->getMessageId());
        $clientMessage->setTimestamp($message->getTimestamp());
        $clientMessage->setPriority(MessagePriority::NORMAL);
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
     * @return FsDestination
     */
    private function createRouterTopic()
    {
        return $this->context->createTopic(
            $this->config->createTransportQueueName($this->config->getRouterTopicName())
        );
    }
}
