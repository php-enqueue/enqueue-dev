<?php

namespace Enqueue\Stomp;

use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrDestination;

class StompContext implements PsrContext
{
    /**
     * @var BufferedStompClient
     */
    private $stomp;

    /**
     * @var callable
     */
    private $stompFactory;

    /**
     * @param BufferedStompClient|callable $stomp
     */
    public function __construct($stomp)
    {
        if ($stomp instanceof BufferedStompClient) {
            $this->stomp = $stomp;
        } elseif (is_callable($stomp)) {
            $this->stompFactory = $stomp;
        } else {
            throw new \InvalidArgumentException('The stomp argument must be either BufferedStompClient or callable that return BufferedStompClient.');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return StompMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new StompMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     *
     * @return StompDestination
     */
    public function createQueue($name)
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
     * {@inheritdoc}
     *
     * @return StompDestination
     */
    public function createTemporaryQueue()
    {
        $queue = $this->createQueue(uniqid('', true));
        $queue->setType(StompDestination::TYPE_TEMP_QUEUE);

        return $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return StompDestination
     */
    public function createTopic($name)
    {
        if (0 !== strpos($name, '/')) {
            $destination = new StompDestination();
            $destination->setType(StompDestination::TYPE_EXCHANGE);
            $destination->setStompName($name);

            return $destination;
        }

        return $this->createDestination($name);
    }

    /**
     * @param string $destination
     *
     * @return StompDestination
     */
    public function createDestination($destination)
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
     * {@inheritdoc}
     *
     * @param StompDestination $destination
     *
     * @return StompConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, StompDestination::class);

        return new StompConsumer($this->getStomp(), $destination);
    }

    /**
     * {@inheritdoc}
     *
     * @return StompProducer
     */
    public function createProducer()
    {
        return new StompProducer($this->getStomp());
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->getStomp()->disconnect();
    }

    /**
     * @return BufferedStompClient
     */
    private function getStomp()
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
