<?php
namespace Enqueue\Stomp;

use Enqueue\Psr\Context;
use Enqueue\Psr\Destination;
use Enqueue\Psr\InvalidDestinationException;

class StompContext implements Context
{
    /**
     * @var BufferedStompClient
     */
    private $stomp;

    /**
     * @param BufferedStompClient $stomp
     */
    public function __construct(BufferedStompClient $stomp)
    {
        $this->stomp = $stomp;
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
    public function createConsumer(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, StompDestination::class);

        return new StompConsumer($this->stomp, $destination);
    }

    /**
     * {@inheritdoc}
     *
     * @return StompProducer
     */
    public function createProducer()
    {
        return new StompProducer($this->stomp);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->stomp->disconnect();
    }
}
