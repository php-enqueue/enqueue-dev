<?php

declare(strict_types=1);

namespace Enqueue\Pheanstalk;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;

class PheanstalkConsumer implements Consumer
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
    public function getQueue(): Queue
    {
        return $this->destination;
    }

    /**
     * @return PheanstalkMessage
     */
    public function receive(int $timeout = 0): ?Message
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
    public function receiveNoWait(): ?Message
    {
        if ($job = $this->pheanstalk->reserveFromTube($this->destination->getName(), 0)) {
            return $this->convertJobToMessage($job);
        }

        return null;
    }

    /**
     * @param PheanstalkMessage $message
     */
    public function acknowledge(Message $message): void
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
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, PheanstalkMessage::class);

        if (false == $message->getJob()) {
            throw new \LogicException(sprintf(
                'The message could not be %s because it does not have job set.',
                $requeue ? 'requeued' : 'rejected'
            ));
        }

        if ($requeue) {
            $this->pheanstalk->release($message->getJob(), $message->getPriority(), $message->getDelay());

            return;
        }

        $this->acknowledge($message);
    }

    private function convertJobToMessage(Job $job): PheanstalkMessage
    {
        $stats = $this->pheanstalk->statsJob($job);

        $message = PheanstalkMessage::jsonUnserialize($job->getData());
        if (isset($stats['reserves'])) {
            $message->setRedelivered($stats['reserves'] > 1);
        }
        $message->setJob($job);

        return $message;
    }
}
