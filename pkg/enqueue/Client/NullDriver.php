<?php

namespace Enqueue\Client;

use Enqueue\Psr\Message as TransportMessage;
use Enqueue\Transport\Null\NullConnectionFactory;
use Enqueue\Transport\Null\NullContext;
use Enqueue\Transport\Null\NullMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NullDriver implements DriverInterface
{
    /**
     * @var NullConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var NullContext
     */
    private $context;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param NullConnectionFactory $connectionFactory
     * @param Config      $config
     */
    public function __construct(NullConnectionFactory $connectionFactory, Config $config)
    {
        $this->connectionFactory = $connectionFactory;
        $this->context = $connectionFactory->createContext();
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @return NullMessage
     */
    public function createTransportMessage(Message $message)
    {
        $headers = $message->getHeaders();
        $headers['content_type'] = $message->getContentType();
        $headers['expiration'] = $message->getExpire();
        $headers['delay'] = $message->getDelay();
        $headers['priority'] = $message->getPriority();

        $transportMessage = $this->context->createMessage();
        $transportMessage->setBody($message->getBody());
        $transportMessage->setHeaders($headers);
        $transportMessage->setProperties($message->getProperties());
        $transportMessage->setTimestamp($message->getTimestamp());
        $transportMessage->setMessageId($message->getMessageId());

        return $transportMessage;
    }

    /**
     * {@inheritdoc}
     *
     * @param NullMessage $message
     */
    public function createClientMessage(TransportMessage $message)
    {
        $clientMessage = new Message();
        $clientMessage->setBody($message->getBody());
        $clientMessage->setHeaders($message->getHeaders());
        $clientMessage->setProperties($message->getProperties());
        $clientMessage->setTimestamp($message->getTimestamp());
        $clientMessage->setMessageId($message->getMessageId());

        if ($contentType = $message->getHeader('content_type')) {
            $clientMessage->setContentType($contentType);
        }

        if ($expiration = $message->getHeader('expiration')) {
            $clientMessage->setExpire($expiration);
        }

        if ($delay = $message->getHeader('delay')) {
            $clientMessage->setDelay($delay);
        }

        if ($priority = $message->getHeader('priority')) {
            $clientMessage->setPriority($priority);
        }

        return $clientMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        return $this->context->createQueue($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function sendToRouter(Message $message)
    {
        $transportMessage = $this->createTransportMessage($message);
        $topic = $this->context->createTopic(
            $this->config->createTransportRouterTopicName(
                $this->config->getRouterTopicName()
            )
        );

        $this->context->createProducer()->send($topic, $transportMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function sendToProcessor(Message $message)
    {
        $transportMessage = $this->createTransportMessage($message);
        $queue = $this->context->createQueue(
            $this->config->createTransportQueueName(
                $this->config->getRouterQueueName()
            )
        );

        $this->context->createProducer()->send($queue, $transportMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function setupBroker(LoggerInterface $logger = null)
    {
        $logger ?: new NullLogger();
        $logger->debug('[NullDriver] nothing to setup');
    }
}
