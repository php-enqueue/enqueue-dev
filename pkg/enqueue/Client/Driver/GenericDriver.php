<?php

declare(strict_types=1);

namespace Enqueue\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\DriverSendResult;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use Interop\Queue\Topic as InteropTopic;
use Psr\Log\LoggerInterface;

class GenericDriver implements DriverInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var RouteCollection
     */
    private $routeCollection;

    public function __construct(
        Context $context,
        Config $config,
        RouteCollection $routeCollection
    ) {
        $this->context = $context;
        $this->config = $config;
        $this->routeCollection = $routeCollection;
    }

    public function sendToRouter(Message $message): DriverSendResult
    {
        if ($message->getProperty(Config::COMMAND)) {
            throw new \LogicException('Command must not be send to router but go directly to its processor.');
        }
        if (false == $message->getProperty(Config::TOPIC)) {
            throw new \LogicException('Topic name parameter is required but is not set');
        }

        $topic = $this->createRouterTopic();
        $transportMessage = $this->createTransportMessage($message);
        $producer = $this->getContext()->createProducer();

        $this->doSendToRouter($producer, $topic, $transportMessage);

        return new DriverSendResult($topic, $transportMessage);
    }

    public function sendToProcessor(Message $message): DriverSendResult
    {
        $topic = $message->getProperty(Config::TOPIC);
        $command = $message->getProperty(Config::COMMAND);

        /** @var InteropQueue $queue */
        $queue = null;
        $routerProcessor = $this->config->getRouterProcessor();
        $processor = $message->getProperty(Config::PROCESSOR);
        if ($topic && $processor && $processor !== $routerProcessor) {
            $route = $this->routeCollection->topicAndProcessor($topic, $processor);
            if (false == $route) {
                throw new \LogicException(sprintf('There is no route for topic "%s" and processor "%s"', $topic, $processor));
            }

            $message->setProperty(Config::PROCESSOR, $route->getProcessor());
            $queue = $this->createRouteQueue($route);
        } elseif ($topic && (false == $processor || $processor === $routerProcessor)) {
            $message->setProperty(Config::PROCESSOR, $routerProcessor);

            $queue = $this->createQueue($this->config->getRouterQueue());
        } elseif ($command) {
            $route = $this->routeCollection->command($command);
            if (false == $route) {
                throw new \LogicException(sprintf('There is no route for command "%s".', $command));
            }

            $message->setProperty(Config::PROCESSOR, $route->getProcessor());
            $queue = $this->createRouteQueue($route);
        } else {
            throw new \LogicException('Either topic or command parameter must be set.');
        }

        $transportMessage = $this->createTransportMessage($message);

        $producer = $this->context->createProducer();

        if (null !== $delay = $transportMessage->getProperty(Config::DELAY)) {
            $producer->setDeliveryDelay($delay * 1000);
        }

        if (null !== $expire = $transportMessage->getProperty(Config::EXPIRE)) {
            $producer->setTimeToLive($expire * 1000);
        }

        if (null !== $priority = $transportMessage->getProperty(Config::PRIORITY)) {
            $priorityMap = $this->getPriorityMap();

            $producer->setPriority($priorityMap[$priority]);
        }

        $this->doSendToProcessor($producer, $queue, $transportMessage);

        return new DriverSendResult($queue, $transportMessage);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
    }

    public function createQueue(string $clientQueueName, bool $prefix = true): InteropQueue
    {
        $transportName = $this->createTransportQueueName($clientQueueName, $prefix);

        return $this->doCreateQueue($transportName);
    }

    public function createRouteQueue(Route $route): InteropQueue
    {
        $transportName = $this->createTransportQueueName(
            $route->getQueue() ?: $this->config->getDefaultQueue(),
            $route->isPrefixQueue()
        );

        return $this->doCreateQueue($transportName);
    }

    public function createTransportMessage(Message $clientMessage): InteropMessage
    {
        $headers = $clientMessage->getHeaders();
        $properties = $clientMessage->getProperties();

        $transportMessage = $this->context->createMessage();
        $transportMessage->setBody($clientMessage->getBody());
        $transportMessage->setHeaders($headers);
        $transportMessage->setProperties($properties);
        $transportMessage->setMessageId($clientMessage->getMessageId());
        $transportMessage->setTimestamp($clientMessage->getTimestamp());
        $transportMessage->setReplyTo($clientMessage->getReplyTo());
        $transportMessage->setCorrelationId($clientMessage->getCorrelationId());

        if ($contentType = $clientMessage->getContentType()) {
            $transportMessage->setProperty(Config::CONTENT_TYPE, $contentType);
        }

        if ($priority = $clientMessage->getPriority()) {
            $transportMessage->setProperty(Config::PRIORITY, $priority);
        }

        if ($expire = $clientMessage->getExpire()) {
            $transportMessage->setProperty(Config::EXPIRE, $expire);
        }

        if ($delay = $clientMessage->getDelay()) {
            $transportMessage->setProperty(Config::DELAY, $delay);
        }

        return $transportMessage;
    }

    public function createClientMessage(InteropMessage $transportMessage): Message
    {
        $clientMessage = new Message();

        $clientMessage->setBody($transportMessage->getBody());
        $clientMessage->setHeaders($transportMessage->getHeaders());
        $clientMessage->setProperties($transportMessage->getProperties());
        $clientMessage->setMessageId($transportMessage->getMessageId());
        $clientMessage->setTimestamp($transportMessage->getTimestamp());
        $clientMessage->setReplyTo($transportMessage->getReplyTo());
        $clientMessage->setCorrelationId($transportMessage->getCorrelationId());

        if ($contentType = $transportMessage->getProperty(Config::CONTENT_TYPE)) {
            $clientMessage->setContentType($contentType);
        }

        if ($priority = $transportMessage->getProperty(Config::PRIORITY)) {
            $clientMessage->setPriority($priority);
        }

        if ($delay = $transportMessage->getProperty(Config::DELAY)) {
            $clientMessage->setDelay((int) $delay);
        }

        if ($expire = $transportMessage->getProperty(Config::EXPIRE)) {
            $clientMessage->setExpire((int) $expire);
        }

        return $clientMessage;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->routeCollection;
    }

    protected function doSendToRouter(InteropProducer $producer, Destination $topic, InteropMessage $transportMessage): void
    {
        $producer->send($topic, $transportMessage);
    }

    protected function doSendToProcessor(InteropProducer $producer, InteropQueue $queue, InteropMessage $transportMessage): void
    {
        $producer->send($queue, $transportMessage);
    }

    protected function createRouterTopic(): Destination
    {
        return $this->createQueue($this->getConfig()->getRouterQueue());
    }

    protected function createTransportRouterTopicName(string $name, bool $prefix): string
    {
        $clientPrefix = $prefix ? $this->config->getPrefix() : '';

        return strtolower(implode($this->config->getSeparator(), array_filter([$clientPrefix, $name])));
    }

    protected function createTransportQueueName(string $name, bool $prefix): string
    {
        $clientPrefix = $prefix ? $this->config->getPrefix() : '';
        $clientAppName = $prefix ? $this->config->getApp() : '';

        return strtolower(implode($this->config->getSeparator(), array_filter([$clientPrefix, $clientAppName, $name])));
    }

    protected function doCreateQueue(string $transportQueueName): InteropQueue
    {
        return $this->context->createQueue($transportQueueName);
    }

    protected function doCreateTopic(string $transportTopicName): InteropTopic
    {
        return $this->context->createTopic($transportTopicName);
    }

    /**
     * [client message priority => transport message priority].
     *
     * @return int[]
     */
    protected function getPriorityMap(): array
    {
        return [
            MessagePriority::VERY_LOW => 0,
            MessagePriority::LOW => 1,
            MessagePriority::NORMAL => 2,
            MessagePriority::HIGH => 3,
            MessagePriority::VERY_HIGH => 4,
        ];
    }
}
