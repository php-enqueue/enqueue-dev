<?php

namespace Enqueue\Pheanstalk;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;

class PheanstalkConsumer implements PsrConsumer
{
    /**
     * @var PheanstalkDestination
     */
    private $destination;

    /**
     * @var Pheanstalk
     */
    private $pheanstalk;

    public function __construct(PheanstalkDestination $destination, Pheanstalk $pheanstalk)
    {
        $this->destination = $destination;
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * @return PheanstalkDestination
     */
    public function getQueue(): PsrQueue
    {
        return $this->destination;
    }

    /**
     * @return PheanstalkMessage
     */
    public function receive(int $timeout = 0): ?PsrMessage
    {
        if (0 === $timeout) {
            while (true) {
                if ($job = $this->pheanstalk->reserveFromTube($this->destination->getName(), 5)) {
                    return $this->convertJobToMessage($job);
                }
            }
        } else {
            if ($job = $this->pheanstalk->reserveFromTube($this->destination->getName(), $timeout / 1000)) {
                return $this->convertJobToMessage($job);
            }
        }

        return null;
    }

    /**
     * @return PheanstalkMessage
     */
    public function receiveNoWait(): ?PsrMessage
    {
        if ($job = $this->pheanstalk->reserveFromTube($this->destination->getName(), 0)) {
            return $this->convertJobToMessage($job);
        }

        return null;
    }

    /**
     * @param PheanstalkMessage $message
     */
    public function acknowledge(PsrMessage $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, PheanstalkMessage::class);

        if (false == $message->getJob()) {
            throw new \LogicException('The message could not be acknowledged because it does not have job set.');
        }

        $this->pheanstalk->delete($message->getJob());
    }

    /**
     * @param PheanstalkMessage $message
     */
    public function reject(PsrMessage $message, bool $requeue = false): void
    {
        $this->acknowledge($message);

        if ($requeue) {
            $this->pheanstalk->release($message->getJob(), $message->getPriority(), $message->getDelay());
        }
    }

    private function convertJobToMessage(Job $job): PheanstalkMessage
    {
        $stats = $this->pheanstalk->statsJob($job);

        $message = PheanstalkMessage::jsonUnserialize($job->getData());
        $message->setRedelivered($stats['reserves'] > 1);
        $message->setJob($job);

        return $message;
    }
}
