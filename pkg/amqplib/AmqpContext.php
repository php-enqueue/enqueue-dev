<?php

namespace Enqueue\Amqplib;

use Interop\Queue\Exception;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrTopic;
use PhpAmqpLib\Connection\AbstractConnection;

class AmqpContext implements PsrContext
{
    private $connection;
    private $channel;

    public function __construct(AbstractConnection $connection)
    {
        $this->connection = $connection;
    }

    public function createMessage($body = null, array $properties = [], array $headers = [])
    {
        return new AmqpMessage($body, $properties, $headers);
    }

    public function createQueue($name)
    {
        return new AmqpQueue($name);
    }

    public function createTopic($name)
    {
        return new AmqpTopic($name);
    }

    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        return new AmqpConsumer($this->getChannel(), $destination);
    }

    public function createProducer()
    {
        return new AmqpProducer($this->getChannel());
    }

    public function createTemporaryQueue()
    {
        $queue = $this->createQueue(null);
        $queue->setExclusive(true);

        $this->declareQueue($queue);

        return $queue;
    }

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
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->channel) {
            $this->channel->close();
        }
    }

    private function getChannel()
    {
        if (null === $this->channel) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }
}
