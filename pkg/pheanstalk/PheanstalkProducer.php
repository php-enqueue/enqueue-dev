<?php

declare(strict_types=1);

namespace Enqueue\Pheanstalk;

use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Pheanstalk\Pheanstalk;

class PheanstalkProducer implements Producer
{
    /**
     * @var Pheanstalk
     */
    private $pheanstalk;

    /**
     * @var int
     */
    private $deliveryDelay;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var int
     */
    private $timeToLive;

    public function __construct(Pheanstalk $pheanstalk)
    {
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * @param PheanstalkDestination $destination
     * @param PheanstalkMessage     $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, PheanstalkDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, PheanstalkMessage::class);

        $rawMessage = json_encode($message);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf('Could not encode value into json. Error %s and message %s', json_last_error(), json_last_error_msg()));
        }

        $this->pheanstalk->useTube($destination->getName())->put(
            $rawMessage,
            $this->resolvePriority($message),
            $this->resolveDelay($message),
            $this->resolveTimeToLive($message)
        );
    }

    /**
     * @return PheanstalkProducer
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        $this->deliveryDelay = $deliveryDelay;

        return $this;
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    /**
     * @return PheanstalkProducer
     */
    public function setPriority(int $priority = null): Producer
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @return PheanstalkProducer
     */
    public function setTimeToLive(int $timeToLive = null): Producer
    {
        $this->timeToLive = $timeToLive;

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }

    private function resolvePriority(PheanstalkMessage $message): ?int
    {
        if (null === $this->priority) {
            return $message->getPriority();
        }

        $priority = $this->priority;
        $this->priority = null;

        return $priority;
    }

    private function resolveDelay(PheanstalkMessage $message): ?int
    {
        if (null === $this->deliveryDelay) {
            return $message->getDelay();
        }

        $delay = $this->deliveryDelay;
        $this->deliveryDelay = null;

        return $delay / 1000;
    }

    private function resolveTimeToLive(PheanstalkMessage $message): ?int
    {
        if (null === $this->timeToLive) {
            return $message->getTimeToRun();
        }

        $ttl = $this->timeToLive;
        $this->timeToLive = null;

        return $ttl / 1000;
    }
}
