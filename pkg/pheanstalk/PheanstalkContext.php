<?php

namespace Enqueue\Pheanstalk;

use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrDestination;
use Pheanstalk\Pheanstalk;

class PheanstalkContext implements PsrContext
{
    /**
     * @var Pheanstalk
     */
    private $pheanstalk;

    /**
     * @param Pheanstalk $pheanstalk
     */
    public function __construct(Pheanstalk $pheanstalk)
    {
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new PheanstalkMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function createTopic($topicName)
    {
        return new PheanstalkDestination($topicName);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        return new PheanstalkDestination($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryQueue()
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     *
     * @return PheanstalkProducer
     */
    public function createProducer()
    {
        return new PheanstalkProducer($this->pheanstalk);
    }

    /**
     * {@inheritdoc}
     *
     * @param PheanstalkDestination $destination
     *
     * @return PheanstalkConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, PheanstalkDestination::class);

        return new PheanstalkConsumer($destination, $this->pheanstalk);
    }

    public function close()
    {
        $this->pheanstalk->getConnection()->disconnect();
    }

    /**
     * @return Pheanstalk
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }
}
