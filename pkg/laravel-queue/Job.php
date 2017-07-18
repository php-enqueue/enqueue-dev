<?php

namespace Enqueue\LaravelQueue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job as BaseJob;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

class Job extends BaseJob implements JobContract
{
    /**
     * @var PsrConsumer
     */
    private $psrConsumer;

    /**
     * @var PsrMessage
     */
    private $psrMessage;

    /**
     * @param Container   $container
     * @param PsrConsumer $psrConsumer
     * @param PsrMessage  $psrMessage
     * @param string      $connectionName
     */
    public function __construct(Container $container, PsrConsumer $psrConsumer, PsrMessage $psrMessage, $connectionName)
    {
        $this->container = $container;
        $this->psrConsumer = $psrConsumer;
        $this->psrMessage = $psrMessage;
        $this->connectionName = $connectionName;
    }

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        $this->psrMessage->setProperty('x-attempts', $this->attempts() + 1);

        parent::fire();
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        parent::delete();

        $this->psrConsumer->acknowledge($this->psrMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function release($delay = 0)
    {
        $this->psrConsumer->reject($this->psrMessage, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return $this->psrConsumer->getQueue()->getQueueName();
    }

    /**
     * {@inheritdoc}
     */
    public function attempts()
    {
        return $this->psrMessage->getProperty('x-attempts', 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getRawBody()
    {
        return $this->psrMessage->getBody();
    }
}
