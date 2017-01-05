<?php

namespace  Enqueue\AmqpExt\Client;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\AmqpMessage;
use Enqueue\AmqpExt\AmqpQueue;
use Enqueue\AmqpExt\AmqpTopic;
use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Psr\DeliveryMode;
use Enqueue\Psr\Message as TransportMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AmqpDriver implements DriverInterface
{
    /**
     * @var AmqpConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var AmqpContext
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
    private $priorityMap;

    /**
     * @param AmqpConnectionFactory       $connectionFactory
     * @param Config            $config
     * @param QueueMetaRegistry $queueMetaRegistry
     */
    public function __construct(AmqpConnectionFactory $connectionFactory, Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
        $this->connectionFactory = $connectionFactory;
        $this->config = $config;
        $this->queueMetaRegistry = $queueMetaRegistry;

        $this->priorityMap = [
            MessagePriority::VERY_LOW => 0,
            MessagePriority::LOW => 1,
            MessagePriority::NORMAL => 2,
            MessagePriority::HIGH => 3,
            MessagePriority::VERY_HIGH => 4,
        ];
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

        $this->getContext()->createProducer()->send($topic, $transportMessage);
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

        $this->getContext()->createProducer()->send($destination, $transportMessage);
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
        $routerTopic = $this->createRouterTopic();
        $routerQueue = $this->createQueue($this->config->getRouterQueueName());

        $log('Declare router exchange: %s', $routerTopic->getTopicName());
        $this->getContext()->declareTopic($routerTopic);
        $log('Declare router queue: %s', $routerQueue->getQueueName());
        $this->getContext()->declareQueue($routerQueue);
        $log('Bind router queue to exchange: %s -> %s', $routerQueue->getQueueName(), $routerTopic->getTopicName());
        $this->getContext()->bind($routerTopic, $routerQueue);

        // setup queues
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
            $queue = $this->createQueue($meta->getClientName());

            $log('Declare processor queue: %s', $queue->getQueueName());
            $this->getContext()->declareQueue($queue);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpQueue
     */
    public function createQueue($queueName)
    {
        $queue = $this->getContext()->createQueue($this->config->createTransportQueueName($queueName));
        $queue->addFlag(AMQP_DURABLE);
        $queue->setArguments(['x-max-priority' => 4]);

        return $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage
     */
    public function createTransportMessage(Message $message)
    {
        $headers = $message->getHeaders();
        $properties = $message->getProperties();

        $headers['content_type'] = $message->getContentType();

        if ($message->getExpire()) {
            $headers['expiration'] = (string) ($message->getExpire() * 1000);
        }

        if ($priority = $message->getPriority()) {
            if (false == array_key_exists($priority, $this->priorityMap)) {
                throw new \InvalidArgumentException(sprintf(
                    'Given priority could not be converted to client\'s one. Got: %s',
                    $priority
                ));
            }

            $headers['priority'] = $this->priorityMap[$priority];
        }

        $headers['delivery_mode'] = DeliveryMode::PERSISTENT;

        $transportMessage = $this->getContext()->createMessage();
        $transportMessage->setBody($message->getBody());
        $transportMessage->setHeaders($headers);
        $transportMessage->setProperties($properties);
        $transportMessage->setMessageId($message->getMessageId());
        $transportMessage->setTimestamp($message->getTimestamp());

        return $transportMessage;
    }

    /**
     * @param AmqpMessage $message
     *
     * {@inheritdoc}
     */
    public function createClientMessage(TransportMessage $message)
    {
        $clientMessage = new Message();

        $clientMessage->setBody($message->getBody());
        $clientMessage->setHeaders($message->getHeaders());
        $clientMessage->setProperties($message->getProperties());

        $clientMessage->setContentType($message->getHeader('content_type'));

        if ($expiration = $message->getHeader('expiration')) {
            if (false == is_numeric($expiration)) {
                throw new \LogicException(sprintf('expiration header is not numeric. "%s"', $expiration));
            }

            $clientMessage->setExpire((int) ((int) $expiration) / 1000);
        }

        if ($priority = $message->getHeader('priority')) {
            if (false === $clientPriority = array_search($priority, $this->priorityMap, true)) {
                throw new \LogicException(sprintf('Cant convert transport priority to client: "%s"', $priority));
            }

            $clientMessage->setPriority($clientPriority);
        }

        $clientMessage->setMessageId($message->getMessageId());
        $clientMessage->setTimestamp($message->getTimestamp());

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
     * @return AmqpTopic
     */
    private function createRouterTopic()
    {
        $topic = $this->getContext()->createTopic(
            $this->config->createTransportRouterTopicName($this->config->getRouterTopicName())
        );
        $topic->setType(AMQP_EX_TYPE_FANOUT);
        $topic->addFlag(AMQP_DURABLE);

        return $topic;
    }

    /**
     * @return AmqpContext
     */
    protected function getContext()
    {
        if (false == $this->context) {
            $this->context = $this->connectionFactory->createContext();
        }

        return $this->context;
    }
}
