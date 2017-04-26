<?php
namespace Enqueue\Sqs\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Psr\PsrMessage;
use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Enqueue\Sqs\SqsMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SqsDriver implements DriverInterface
{
    /**
     * @var SqsContext
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
     * @param SqsContext        $context
     * @param Config            $config
     * @param QueueMetaRegistry $queueMetaRegistry
     */
    public function __construct(SqsContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry)
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

        $queue = $this->createQueue($this->config->getRouterQueueName());
        $transportMessage = $this->createTransportMessage($message);

        $this->context->createProducer()->send($queue, $transportMessage);
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
     *
     * @return SqsDestination
     */
    public function createQueue($queueName)
    {
        $transportName = $this->queueMetaRegistry->getQueueMeta($queueName)->getTransportName();
        $transportName = str_replace('.', '_dot_', $transportName);

        return $this->context->createQueue($transportName);
    }

    /**
     * {@inheritdoc}
     */
    public function setupBroker(LoggerInterface $logger = null)
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[AmqpDriver] '.$text, ...$args));
        };

        // setup router
        $routerQueue = $this->createQueue($this->config->getRouterQueueName());
        $log('Declare router queue: %s', $routerQueue->getQueueName());
        $this->context->declareQueue($routerQueue);

        // setup queues
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
            $queue = $this->createQueue($meta->getClientName());

            $log('Declare processor queue: %s', $queue->getQueueName());
            $this->context->declareQueue($queue);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return SqsMessage
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
     * @param SqsMessage $message
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
}
