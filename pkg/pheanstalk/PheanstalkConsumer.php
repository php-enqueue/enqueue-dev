<?php

namespace Enqueue\Pheanstalk;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
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

    /**
     * @param PheanstalkDestination $destination
     * @param Pheanstalk            $pheanstalk
     */
    public function __construct(PheanstalkDestination $destination, Pheanstalk $pheanstalk)
    {
        $this->destination = $destination;
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * {@inheritdoc}
     *
     * @return PheanstalkDestination
     */
    public function getQueue()
    {
        return $this->destination;
    }

    /**
     * {@inheritdoc}
     *
     * @return PheanstalkMessage|null
     */
    public function receive($timeout = 0)
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
    }

    /**
     * {@inheritdoc}
     *
     * @return PheanstalkMessage|null
     */
    public function receiveNoWait()
    {
        if ($job = $this->pheanstalk->reserveFromTube($this->destination->getName(), 0)) {
            return $this->convertJobToMessage($job);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param PheanstalkMessage $message
     */
    public function acknowledge(PsrMessage $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, PheanstalkMessage::class);

        if (false == $message->getJob()) {
            throw new \LogicException('The message could not be acknowledged because it does not have job set.');
        }

        $this->pheanstalk->delete($message->getJob());
    }

    /**
     * {@inheritdoc}
     *
     * @param PheanstalkMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        $this->acknowledge($message);

        if ($requeue) {
            $this->pheanstalk->release($message->getJob(), $message->getPriority(), $message->getDelay());
        }
    }

    /**
     * @param Job $job
     *
     * @return PheanstalkMessage
     */
    private function convertJobToMessage(Job $job)
    {
        $stats = $this->pheanstalk->statsJob($job);

        $message = PheanstalkMessage::jsonUnserialize($job->getData());
        $message->setRedelivered($stats['reserves'] > 1);
        $message->setJob($job);

        return $message;
    }
}
