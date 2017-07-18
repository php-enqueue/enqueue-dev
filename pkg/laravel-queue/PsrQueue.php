<?php

namespace Enqueue\LaravelQueue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Interop\Queue\PsrContext;

class PsrQueue extends Queue implements QueueContract
{
    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * The "time to run" for all pushed jobs.
     *
     * @var int
     */
    protected $timeToRun;
    /**
     * @var PsrContext
     */
    private $psrContext;

    /**
     * @param PsrContext $psrContext
     * @param string     $default
     * @param int        $timeToRun
     */
    public function __construct(PsrContext $psrContext, $default, $timeToRun)
    {
        $this->psrContext = $psrContext;
        $this->default = $default;
        $this->timeToRun = $timeToRun;
    }

    /**
     * {@inheritdoc}
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string $queue
     * @param string $job
     * @param mixed  $data
     *
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->psrContext->createProducer()->send(
            $this->getQueue($queue),
            $this->psrContext->createMessage($payload)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        new \LogicException('to be implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $psrConsumer = $this->psrContext->createConsumer($queue);
        if ($psrMessage = $psrConsumer->receive(1000)) { // 1 sec
            return new Job(
                $this->container,
                $psrConsumer,
                $psrMessage,
                $this->connectionName
            );
        }
    }

    /**
     * Get the queue or return the default.
     *
     * @param string|null $queue
     *
     * @return \Interop\Queue\PsrQueue
     */
    public function getQueue($queue)
    {
        return $this->psrContext->createQueue($queue ?: $this->default);
    }
}
