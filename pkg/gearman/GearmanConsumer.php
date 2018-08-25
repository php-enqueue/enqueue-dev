<?php

namespace Enqueue\Gearman;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;

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

    public function __construct(GearmanContext $context, GearmanDestination $destination)
    {
        $this->context = $context;
        $this->destination = $destination;

        $this->worker = $context->createWorker();
    }

    /**
     * @return GearmanDestination
     */
    public function getQueue(): PsrQueue
    {
        return $this->destination;
    }

    /**
     * @return GearmanMessage
     */
    public function receive(int $timeout = 0): ?PsrMessage
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
     * @return GearmanMessage
     */
    public function receiveNoWait(): ?PsrMessage
    {
        return $this->receive(100);
    }

    /**
     * @param GearmanMessage $message
     */
    public function acknowledge(PsrMessage $message): void
    {
    }

    /**
     * @param GearmanMessage $message
     */
    public function reject(PsrMessage $message, bool $requeue = false): void
    {
        if ($requeue) {
            $this->context->createProducer()->send($this->destination, $message);
        }
    }

    public function getWorker(): \GearmanWorker
    {
        return $this->worker;
    }
}
