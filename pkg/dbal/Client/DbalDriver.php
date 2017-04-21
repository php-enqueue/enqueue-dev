<?php
namespace Enqueue\Dbal\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Psr\PsrMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DbalDriver implements DriverInterface
{
    /**
     * @var DbalContext
     */
    private $context;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param DbalContext $context
     * @param Config      $config
     */
    public function __construct(DbalContext $context, Config $config)
    {
        $this->context = $context;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalMessage
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
        $transportMessage->setDelay($message->getDelay());

        return $transportMessage;
    }

    /**
     * @param DbalMessage $message
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
        $clientMessage->setDelay($message->getDelay());

        return $clientMessage;
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
    public function createQueue($queueName)
    {
        return $this->context->createQueue($this->config->createTransportQueueName($queueName));
    }

    /**
     * {@inheritdoc}
     */
    public function setupBroker(LoggerInterface $logger = null)
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[DbalDriver] '.$text, ...$args));
        };

        $log('Creating database table: "%s"', $this->context->getTableName());
        $this->context->createDataBaseTable();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return DbalDestination
     */
    private function createRouterTopic()
    {
        return $this->context->createTopic(
            $this->config->createTransportQueueName($this->config->getRouterTopicName())
        );
    }
}
