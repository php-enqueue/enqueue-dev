<?php

namespace Enqueue\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbDestination;
use Enqueue\Mongodb\MongodbMessage;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MongodbDriver implements DriverInterface
{
    /**
     * @var MongodbContext
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
     * @var array
     */
    private static $priorityMap = [
        MessagePriority::VERY_LOW => 0,
        MessagePriority::LOW => 1,
        MessagePriority::NORMAL => 2,
        MessagePriority::HIGH => 3,
        MessagePriority::VERY_HIGH => 4,
    ];

    public function __construct(MongodbContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
        $this->context = $context;
        $this->config = $config;
        $this->queueMetaRegistry = $queueMetaRegistry;
    }

    /**
     * @return MongodbMessage
     */
    public function createTransportMessage(Message $message): PsrMessage
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
        $transportMessage->setDeliveryDelay($message->getDelay());
        $transportMessage->setReplyTo($message->getReplyTo());
        $transportMessage->setCorrelationId($message->getCorrelationId());
        if (array_key_exists($message->getPriority(), self::$priorityMap)) {
            $transportMessage->setPriority(self::$priorityMap[$message->getPriority()]);
        }

        return $transportMessage;
    }

    /**
     * @param MongodbMessage $message
     */
    public function createClientMessage(PsrMessage $message): Message
    {
        $clientMessage = new Message();

        $clientMessage->setBody($message->getBody());
        $clientMessage->setHeaders($message->getHeaders());
        $clientMessage->setProperties($message->getProperties());

        $clientMessage->setContentType($message->getHeader('content_type'));
        $clientMessage->setMessageId($message->getMessageId());
        $clientMessage->setTimestamp($message->getTimestamp());
        $clientMessage->setDelay($message->getDeliveryDelay());
        $clientMessage->setReplyTo($message->getReplyTo());
        $clientMessage->setCorrelationId($message->getCorrelationId());

        $priorityMap = array_flip(self::$priorityMap);
        $priority = array_key_exists($message->getPriority(), $priorityMap) ?
            $priorityMap[$message->getPriority()] :
            MessagePriority::NORMAL;
        $clientMessage->setPriority($priority);

        return $clientMessage;
    }

    public function sendToRouter(Message $message): void
    {
        if (false == $message->getProperty(Config::PARAMETER_TOPIC_NAME)) {
            throw new \LogicException('Topic name parameter is required but is not set');
        }

        $queue = $this->createQueue($this->config->getRouterQueueName());
        $transportMessage = $this->createTransportMessage($message);

        $this->context->createProducer()->send($queue, $transportMessage);
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
     * @return MongodbDestination
     */
    public function createQueue(string $queueName): PsrQueue
    {
        $transportName = $this->queueMetaRegistry->getQueueMeta($queueName)->getTransportName();

        return $this->context->createQueue($transportName);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[MongodbDriver] '.$text, ...$args));
        };
        $contextConfig = $this->context->getConfig();
        $log('Creating database and collection: "%s" "%s"', $contextConfig['dbname'], $contextConfig['collection_name']);
        $this->context->createCollection();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public static function getPriorityMap(): array
    {
        return self::$priorityMap;
    }
}
