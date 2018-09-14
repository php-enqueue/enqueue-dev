<?php

declare(strict_types=1);

namespace Enqueue\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;
use Psr\Log\LoggerInterface;

class GenericDriver implements DriverInterface
{
    /**
     * @var PsrContext
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
        PsrContext $context,
        Config $config,
        RouteCollection $routeCollection
    ) {
        $this->context = $context;
        $this->config = $config;
        $this->routeCollection = $routeCollection;
    }

    public function sendToRouter(Message $message): void
    {
        if ($message->getProperty(Config::PARAMETER_COMMAND_NAME)) {
            throw new \LogicException('Command must not be send to router but go directly to its processor.');
        }
        if (false == $message->getProperty(Config::PARAMETER_TOPIC_NAME)) {
            throw new \LogicException('Topic name parameter is required but is not set');
        }

        $topic = $this->createRouterTopic();
        $transportMessage = $this->createTransportMessage($message);

        $this->doSendToRouter($topic, $transportMessage);
    }

    public function sendToProcessor(Message $message): void
    {
        $processor = $message->getProperty(Config::PARAMETER_PROCESSOR_NAME);
        if (false == $processor) {
            throw new \LogicException('Processor name parameter is required but is not set');
        }

        $topic = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        $command = $message->getProperty(Config::PARAMETER_COMMAND_NAME);

        /** @var Route $route */
        $route = null;
        if ($topic) {
            $route = $this->routeCollection->topicAndProcessor($topic, $processor);
            if (false == $route) {
                throw new \LogicException(sprintf('There is no route for topic "%s" and processor "%s"', $topic, $processor));
            }
        } elseif ($command) {
            $route = $this->routeCollection->command($command);
            if (false == $route) {
                throw new \LogicException(sprintf('There is no route for command "%s" and processor "%s"', $command, $processor));
            }

            if ($processor !== $route->getProcessor()) {
                throw new \LogicException(sprintf('The command "%s" route was found but processors do not match. Given "%s", route "%s"', $command, $processor, $route->getProcessor()));
            }
        } else {
            throw new \LogicException('Either topic or command parameter must be set.');
        }

        $transportMessage = $this->createTransportMessage($message);
        $queue = $this->createRouteQueue($route);

        $this->doSendToProcessor($queue, $transportMessage);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
    }

    public function createQueue(string $clientQueueName): PsrQueue
    {
        $transportName = $this->createTransportQueueName($clientQueueName, true);

        return $this->context->createQueue($transportName);
    }

    public function createTransportMessage(Message $clientMessage): PsrMessage
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
            $transportMessage->setProperty('X-Enqueue-Content-Type', $contentType);
        }

        if ($priority = $clientMessage->getPriority()) {
            $transportMessage->setProperty('X-Enqueue-Priority', $priority);
        }

        if ($expire = $clientMessage->getExpire()) {
            $transportMessage->setProperty('X-Enqueue-Expire', $expire);
        }

        if ($delay = $clientMessage->getDelay()) {
            $transportMessage->setProperty('X-Enqueue-Delay', $delay);
        }

        return $transportMessage;
    }

    public function createClientMessage(PsrMessage $transportMessage): Message
    {
        $clientMessage = new Message();

        $clientMessage->setBody($transportMessage->getBody());
        $clientMessage->setHeaders($transportMessage->getHeaders());
        $clientMessage->setProperties($transportMessage->getProperties());

        $clientMessage->setMessageId($transportMessage->getMessageId());
        $clientMessage->setTimestamp($transportMessage->getTimestamp());
        $clientMessage->setPriority(MessagePriority::NORMAL);
        $clientMessage->setReplyTo($transportMessage->getReplyTo());
        $clientMessage->setCorrelationId($transportMessage->getCorrelationId());

        if ($contentType = $transportMessage->getProperty('X-Enqueue-Content-Type')) {
            $clientMessage->setContentType($contentType);
        }

        if ($priority = $transportMessage->getProperty('X-Enqueue-Priority')) {
            $clientMessage->setPriority($priority);
        }

        if ($delay = $transportMessage->getProperty('X-Enqueue-Delay')) {
            $clientMessage->setDelay((int) $delay);
        }

        if ($expire = $transportMessage->getProperty('X-Enqueue-Expire')) {
            $clientMessage->setExpire((int) $expire);
        }

        return $clientMessage;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getContext(): PsrContext
    {
        return $this->context;
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->routeCollection;
    }

    protected function doSendToRouter(PsrTopic $topic, PsrMessage $transportMessage): void
    {
        $this->context->createProducer()->send($topic, $transportMessage);
    }

    protected function doSendToProcessor(PsrQueue $queue, PsrMessage $transportMessage): void
    {
        $this->context->createProducer()->send($queue, $transportMessage);
    }

    protected function createRouterTopic(): PsrTopic
    {
        return $this->context->createTopic(
            $this->createTransportRouterTopicName($this->config->getRouterTopicName(), true)
        );
    }

    protected function createRouteQueue(Route $route): PsrQueue
    {
        $transportName = $this->createTransportQueueName(
            $route->getQueue() ?: $this->config->getDefaultProcessorQueueName(),
            $route->isPrefixQueue()
        );

        return $this->context->createQueue($transportName);
    }

    protected function createTransportRouterTopicName(string $name, bool $prefix): string
    {
        $clientPrefix = $prefix ? $this->config->getPrefix() : '';

        return strtolower(implode($this->config->getSeparator(), array_filter([$clientPrefix, $name])));
    }

    protected function createTransportQueueName(string $name, bool $prefix): string
    {
        $clientPrefix = $prefix ? $this->config->getPrefix() : '';
        $clientAppName = $prefix ? $this->config->getAppName() : '';

        return strtolower(implode($this->config->getSeparator(), array_filter([$clientPrefix, $clientAppName, $name])));
    }
}
