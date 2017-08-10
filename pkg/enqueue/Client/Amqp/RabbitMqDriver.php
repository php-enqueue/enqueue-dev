<?php

namespace  Enqueue\Client\Amqp;

use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Consumption\Exception\LogicException;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\PsrMessage;

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
        $producer = $this->context->createProducer();

        if ($message->getDelay()) {
            $producer->setDeliveryDelay($message->getDelay() * 1000);
        }

        $producer->send($destination, $transportMessage);
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

            $transportMessage->setPriority($this->priorityMap[$priority]);
        }

        if ($message->getDelay()) {
            if (false == $this->config->getTransportOption('delay_strategy', false)) {
                throw new LogicException('The message delaying is not supported. In order to use delay feature install RabbitMQ delay strategy.');
            }

            $transportMessage->setProperty('enqueue-delay', $message->getDelay() * 1000);
        }

        return $transportMessage;
    }

    /**
     * @param AmqpMessage $message
     *
     * {@inheritdoc}
     */
    public function createClientMessage(PsrMessage $message)
    {
        $clientMessage = parent::createClientMessage($message);

        if ($priority = $message->getPriority()) {
            if (false === $clientPriority = array_search($priority, $this->priorityMap, true)) {
                throw new \LogicException(sprintf('Cant convert transport priority to client: "%s"', $priority));
            }

            $clientMessage->setPriority($clientPriority);
        }

        if ($delay = $message->getProperty('enqueue-delay')) {
            if (false == is_numeric($delay)) {
                throw new \LogicException(sprintf('"enqueue-delay" header is not numeric. "%s"', $delay));
            }

            $clientMessage->setDelay((int) ((int) $delay) / 1000);
        }

        return $clientMessage;
    }
}
