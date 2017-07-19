<?php

namespace Enqueue\AmqpLib;

use Interop\Queue\Exception;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

class AmqpContext implements PsrContext
{
    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @param AbstractConnection $connection
     */
    public function __construct(AbstractConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string|null $body
     * @param array       $properties
     * @param array       $headers
     *
     * @return AmqpMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new AmqpMessage($body, $properties, $headers);
    }

    /**
     * @param string $name
     *
     * @return AmqpQueue
     */
    public function createQueue($name)
    {
        return new AmqpQueue($name);
    }

    /**
     * @param string $name
     *
     * @return AmqpTopic
     */
    public function createTopic($name)
    {
        return new AmqpTopic($name);
    }

    /**
     * @param PsrDestination $destination
     *
     * @return AmqpConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        $destination instanceof PsrTopic
            ? InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class)
        ;

        if ($destination instanceof AmqpTopic) {
            $queue = $this->createTemporaryQueue();
            $this->bind($destination, $queue);

            return new AmqpConsumer($this->getChannel(), $queue);
        }

        return new AmqpConsumer($this->getChannel(), $destination);
    }

    /**
     * @return AmqpProducer
     */
    public function createProducer()
    {
        return new AmqpProducer($this->getChannel());
    }

    /**
     * @return AmqpQueue
     */
    public function createTemporaryQueue()
    {
        $queue = $this->createQueue(null);
        $queue->setExclusive(true);

        $this->declareQueue($queue);

        return $queue;
    }

    /**
     * @param AmqpTopic $destination
     */
    public function declareTopic(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class);

        $this->getChannel()->exchange_declare(
            $destination->getTopicName(),
            $destination->getType(),
            $destination->isPassive(),
            $destination->isDurable(),
            $destination->isAutoDelete(),
            $destination->isInternal(),
            $destination->isNoWait(),
            $destination->getArguments(),
            $destination->getTicket()
        );
    }

    /**
     * @param AmqpQueue $destination
     */
    public function declareQueue(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        $this->getChannel()->queue_declare(
            $destination->getQueueName(),
            $destination->isPassive(),
            $destination->isDurable(),
            $destination->isExclusive(),
            $destination->isAutoDelete(),
            $destination->isNoWait(),
            $destination->getArguments(),
            $destination->getTicket()
        );
    }

    /**
     * @param AmqpTopic|AmqpQueue $source
     * @param AmqpTopic|AmqpQueue $target
     *
     * @throws Exception
     */
    public function bind(PsrDestination $source, PsrDestination $target)
    {
        $source instanceof PsrTopic
            ? InvalidDestinationException::assertDestinationInstanceOf($source, AmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($source, AmqpQueue::class)
        ;

        $target instanceof PsrTopic
            ? InvalidDestinationException::assertDestinationInstanceOf($target, AmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($target, AmqpQueue::class)
        ;

        if ($source instanceof AmqpQueue && $target instanceof AmqpQueue) {
            throw new Exception('Is not possible to bind queue to queue. It is possible to bind topic to queue or topic to topic');
        }

        // bind exchange to exchange
        if ($source instanceof AmqpTopic && $target instanceof AmqpTopic) {
            $this->getChannel()->exchange_bind(
                $target->getTopicName(),
                $source->getTopicName(),
                $source->getRoutingKey(),
                $source->isNowait(),
                $source->getArguments(),
                $source->getTicket()
            );
        // bind queue to exchange
        } elseif ($source instanceof AmqpQueue) {
            $this->getChannel()->queue_bind(
                $source->getQueueName(),
                $target->getTopicName(),
                $target->getRoutingKey(),
                $target->isNowait(),
                $target->getArguments(),
                $target->getTicket()
            );
        // bind exchange to queue
        } else {
            $this->getChannel()->queue_bind(
                $target->getQueueName(),
                $source->getTopicName(),
                $source->getRoutingKey(),
                $source->isNowait(),
                $source->getArguments(),
                $source->getTicket()
            );
        }
    }

    /**
     * Purge all messages from the given queue.
     *
     * @param PsrQueue $queue
     */
    public function purge(PsrQueue $queue)
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, AmqpQueue::class);

        $this->getChannel()->queue_purge($queue->getQueueName());
    }

    public function close()
    {
        if ($this->channel) {
            $this->channel->close();
        }
    }

    /**
     * @return AMQPChannel
     */
    private function getChannel()
    {
        if (null === $this->channel) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }
}
