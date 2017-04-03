<?php

namespace  Enqueue\AmqpExt\Client;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\AmqpMessage;
use Enqueue\AmqpExt\AmqpQueue;
use Enqueue\AmqpExt\AmqpTopic;
use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Consumption\Exception\LogicException;
use Enqueue\Psr\Message as TransportMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RabbitMqDriver extends AmqpDriver
{
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
     * @param AmqpContext       $context
     * @param Config            $config
     * @param QueueMetaRegistry $queueMetaRegistry
     */
    public function __construct(AmqpContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry)
    {
        parent::__construct($context, $config, $queueMetaRegistry);

        $this->config = $config;
        $this->context = $context;
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

        if ($message->getDelay()) {
            $destination = $this->createDelayedTopic($destination);
        }

        $this->context->createProducer()->send($destination, $transportMessage);
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpQueue
     */
    public function createQueue($queueName)
    {
        $queue = parent::createQueue($queueName);
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
        $transportMessage = parent::createTransportMessage($message);

        if ($priority = $message->getPriority()) {
            if (false == array_key_exists($priority, $this->priorityMap)) {
                throw new \InvalidArgumentException(sprintf(
                    'Given priority could not be converted to client\'s one. Got: %s',
                    $priority
                ));
            }

            $transportMessage->setHeader('priority', $this->priorityMap[$priority]);
        }

        if ($message->getDelay()) {
            if (false == $this->config->getTransportOption('delay_plugin_installed', false)) {
                throw new LogicException('The message delaying is not supported. In order to use delay feature install RabbitMQ delay plugin.');
            }

            $transportMessage->setProperty('x-delay', (string) ($message->getDelay() * 1000));
        }


        return $transportMessage;
    }

    /**
     * @param AmqpMessage $message
     *
     * {@inheritdoc}
     */
    public function createClientMessage(TransportMessage $message)
    {
        $clientMessage = parent::createClientMessage($message);

        if ($priority = $message->getHeader('priority')) {
            if (false === $clientPriority = array_search($priority, $this->priorityMap, true)) {
                throw new \LogicException(sprintf('Cant convert transport priority to client: "%s"', $priority));
            }

            $clientMessage->setPriority($clientPriority);
        }

        if ($delay = $message->getProperty('x-delay')) {
            if (false == is_numeric($delay)) {
                throw new \LogicException(sprintf('x-delay header is not numeric. "%s"', $delay));
            }

            $clientMessage->setDelay((int) ((int) $delay) / 1000);
        }

        return $clientMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function setupBroker(LoggerInterface $logger = null)
    {
        $logger = $logger ?: new NullLogger();

        parent::setupBroker($logger);

        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[RabbitMqDriver] '.$text, ...$args));
        };

        // setup delay exchanges
        if ($this->config->getTransportOption('delay_plugin_installed', false)) {
            foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
                $queue = $this->createQueue($meta->getClientName());

                $delayTopic = $this->createDelayedTopic($queue);

                $log('Declare delay exchange: %s', $delayTopic->getTopicName());
                $this->context->declareTopic($delayTopic);

                $log('Bind processor queue to delay exchange: %s -> %s', $queue->getQueueName(), $delayTopic->getTopicName());
                $this->context->bind($delayTopic, $queue);
            }
        }
    }

    /**
     * @param AmqpQueue $queue
     *
     * @return AmqpTopic
     */
    private function createDelayedTopic(AmqpQueue $queue)
    {
        $queueName = $queue->getQueueName();

        // in order to use delay feature make sure the rabbitmq_delayed_message_exchange plugin is installed.
        $delayTopic = $this->context->createTopic($queueName.'.delayed');
        $delayTopic->setRoutingKey($queueName);
        $delayTopic->setType('x-delayed-message');
        $delayTopic->addFlag(AMQP_DURABLE);
        $delayTopic->setArguments([
            'x-delayed-type' => 'direct',
        ]);

        return $delayTopic;
    }
}
