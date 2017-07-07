<?php

namespace Enqueue\Gearman;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;

class GearmanContext implements PsrContext
{
    /**
     * @var \GearmanClient
     */
    private $client;

    /**
     * @var GearmanConsumer[]
     */
    private $consumers;

    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @return GearmanMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new GearmanMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     *
     * @return GearmanDestination
     */
    public function createTopic($topicName)
    {
        return new GearmanDestination($topicName);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        return new GearmanDestination($queueName);
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
     * @return GearmanProducer
     */
    public function createProducer()
    {
        return new GearmanProducer($this->getClient());
    }

    /**
     * {@inheritdoc}
     *
     * @param GearmanDestination $destination
     *
     * @return GearmanConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GearmanDestination::class);

        $this->consumers[] = $consumer = new GearmanConsumer($this, $destination);

        return $consumer;
    }

    public function close()
    {
        $this->getClient()->clearCallbacks();

        foreach ($this->consumers as $consumer) {
            $consumer->getWorker()->unregisterAll();
        }
    }

    /**
     * @return \GearmanClient
     */
    public function getClient()
    {
        if (false == $this->client) {
            $this->client = new \GearmanClient();
            $this->client->addServer($this->config['host'], $this->config['port']);
        }

        return $this->client;
    }

    /**
     * @return \GearmanWorker
     */
    public function createWorker()
    {
        $worker = new \GearmanWorker();
        $worker->addServer($this->config['host'], $this->config['port']);

        return $worker;
    }
}
