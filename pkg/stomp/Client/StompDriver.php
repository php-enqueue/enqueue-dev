<?php
namespace Enqueue\Stomp\Client;

use Enqueue\Psr\Message as TransportMessage;
use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class StompDriver implements DriverInterface
{
    /**
     * @var StompContext
     */
    private $context;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param StompContext $context
     * @param Config       $config
     */
    public function __construct(StompContext $context, Config $config)
    {
        $this->context = $context;
        $this->config = $config;
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
        $logger->debug('[StompDriver] Stomp protocol does not support broker configuration');
    }

    /**
     * @return StompMessage
     *
     * {@inheritdoc}
     */
    public function createTransportMessage(Message $message)
    {
        $headers = $message->getHeaders();
        $headers['content-type'] = $message->getContentType();

        $transportMessage = $this->context->createMessage();
        $transportMessage->setHeaders($headers);
        $transportMessage->setPersistent(true);
        $transportMessage->setBody($message->getBody());
        $transportMessage->setProperties($message->getProperties());

        if ($message->getMessageId()) {
            $transportMessage->setMessageId($message->getMessageId());
        }

        if ($message->getTimestamp()) {
            $transportMessage->setTimestamp($message->getTimestamp());
        }

        return $transportMessage;
    }

    /**
     * @param StompMessage $message
     *
     * {@inheritdoc}
     */
    public function createClientMessage(TransportMessage $message)
    {
        $clientMessage = new Message();

        $headers = $message->getHeaders();
        unset(
            $headers['content-type'],
            $headers['message_id'],
            $headers['timestamp']
        );

        $clientMessage->setHeaders($headers);
        $clientMessage->setBody($message->getBody());
        $clientMessage->setProperties($message->getProperties());

        $clientMessage->setContentType($message->getHeader('content-type'));

        $clientMessage->setMessageId($message->getMessageId());
        $clientMessage->setTimestamp($message->getTimestamp());

        return $clientMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        $queue = $this->context->createQueue($this->config->createTransportQueueName($queueName));
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
        $queue->setExclusive(false);

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return StompDestination
     */
    private function createRouterTopic()
    {
        $topic = $this->context->createTopic(
            $this->config->createTransportRouterTopicName($this->config->getRouterTopicName())
        );
        $topic->setDurable(true);
        $topic->setAutoDelete(false);

        return $topic;
    }
}
