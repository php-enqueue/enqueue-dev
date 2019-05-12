<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class StompContext implements Context
{
    /**
     * @var BufferedStompClient
     */
    private $stomp;

    /**
     * @var bool
     */
    private $useExchangePrefix;

    /**
     * @var callable
     */
    private $stompFactory;

    /**
     * @param BufferedStompClient|callable $stomp
     * @param bool                         $useExchangePrefix
     */
    public function __construct($stomp, $useExchangePrefix = true)
    {
        if ($stomp instanceof BufferedStompClient) {
            $this->stomp = $stomp;
        } elseif (is_callable($stomp)) {
            $this->stompFactory = $stomp;
        } else {
            throw new \InvalidArgumentException('The stomp argument must be either BufferedStompClient or callable that return BufferedStompClient.');
        }

        $this->useExchangePrefix = $useExchangePrefix;
    }

    /**
     * @return StompMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new StompMessage($body, $properties, $headers);
    }

    /**
     * @return StompDestination
     */
    public function createQueue(string $name): Queue
    {
        if (0 !== strpos($name, '/')) {
            $destination = new StompDestination();
            $destination->setType(StompDestination::TYPE_QUEUE);
            $destination->setStompName($name);

            return $destination;
        }

        return $this->createDestination($name);
    }

    /**
     * @return StompDestination
     */
    public function createTemporaryQueue(): Queue
    {
        $queue = $this->createQueue(uniqid('', true));
        $queue->setType(StompDestination::TYPE_TEMP_QUEUE);

        return $queue;
    }

    /**
     * @return StompDestination
     */
    public function createTopic(string $name): Topic
    {
        if (0 !== strpos($name, '/')) {
            $destination = new StompDestination();
            $destination->setType($this->useExchangePrefix ? StompDestination::TYPE_EXCHANGE : StompDestination::TYPE_TOPIC);
            $destination->setStompName($name);

            return $destination;
        }

        return $this->createDestination($name);
    }

    public function createDestination(string $destination): StompDestination
    {
        $types = [
            StompDestination::TYPE_TOPIC,
            StompDestination::TYPE_EXCHANGE,
            StompDestination::TYPE_QUEUE,
            StompDestination::TYPE_AMQ_QUEUE,
            StompDestination::TYPE_TEMP_QUEUE,
            StompDestination::TYPE_REPLY_QUEUE,
        ];

        $dest = $destination;
        $type = null;
        $name = null;
        $routingKey = null;

        foreach ($types as $_type) {
            $typePrefix = '/'.$_type.'/';
            if (0 === strpos($dest, $typePrefix)) {
                $type = $_type;
                $dest = substr($dest, strlen($typePrefix));

                break;
            }
        }

        if (null === $type) {
            throw new \LogicException(sprintf('Destination name is invalid, cant find type: "%s"', $destination));
        }

        $pieces = explode('/', $dest);

        if (count($pieces) > 2) {
            throw new \LogicException(sprintf('Destination name is invalid, found extra / char: "%s"', $destination));
        }

        if (empty($pieces[0])) {
            throw new \LogicException(sprintf('Destination name is invalid, name is empty: "%s"', $destination));
        }

        $name = $pieces[0];

        if (isset($pieces[1])) {
            if (empty($pieces[1])) {
                throw new \LogicException(sprintf('Destination name is invalid, routing key is empty: "%s"', $destination));
            }

            $routingKey = $pieces[1];
        }

        $destination = new StompDestination();
        $destination->setType($type);
        $destination->setStompName($name);
        $destination->setRoutingKey($routingKey);

        return $destination;
    }

    /**
     * @param StompDestination $destination
     *
     * @return StompConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, StompDestination::class);

        return new StompConsumer($this->getStomp(), $destination);
    }

    /**
     * @return StompProducer
     */
    public function createProducer(): Producer
    {
        return new StompProducer($this->getStomp());
    }

    public function close(): void
    {
        $this->getStomp()->disconnect();
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(Queue $queue): void
    {
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function getStomp(): BufferedStompClient
    {
        if (false == $this->stomp) {
            $stomp = call_user_func($this->stompFactory);
            if (false == $stomp instanceof BufferedStompClient) {
                throw new \LogicException(sprintf(
                    'The factory must return instance of BufferedStompClient. It returns %s',
                    is_object($stomp) ? get_class($stomp) : gettype($stomp)
                ));
            }

            $this->stomp = $stomp;
        }

        return $this->stomp;
    }
}
