<?php

namespace Enqueue\Gearman;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

class GearmanConsumer implements PsrConsumer
{
    /**
     * @var \GearmanWorker
     */
    private $worker;

    /**
     * @var GearmanDestination
     */
    private $destination;

    /**
     * @var GearmanContext
     */
    private $context;

    /**
     * @param GearmanContext     $context
     * @param GearmanDestination $destination
     *
     * @internal param \GearmanWorker $worker
     */
    public function __construct(GearmanContext $context, GearmanDestination $destination)
    {
        $this->context = $context;
        $this->destination = $destination;

        $this->worker = $context->createWorker();
    }

    /**
     * {@inheritdoc}
     *
     * @return GearmanDestination
     */
    public function getQueue()
    {
        return $this->destination;
    }

    /**
     * {@inheritdoc}
     *
     * @return GearmanMessage
     */
    public function receive($timeout = 0)
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        $this->worker->setTimeout($timeout);

        try {
            $message = null;

            $this->worker->addFunction($this->destination->getName(), function (\GearmanJob $job) use (&$message) {
                $message = GearmanMessage::jsonUnserialize($job->workload());
            });

            while ($this->worker->work());
        } finally {
            restore_error_handler();
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function receiveNoWait()
    {
        return $this->receive(100);
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(PsrMessage $message)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        if ($requeue) {
            $this->context->createProducer()->send($this->destination, $message);
        }
    }

    /**
     * @return \GearmanWorker
     */
    public function getWorker()
    {
        return $this->worker;
    }
}
