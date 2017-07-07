<?php

namespace Enqueue\Pheanstalk;

use Interop\Queue\CompletionListener;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Pheanstalk\Pheanstalk;

class PheanstalkProducer implements PsrProducer
{
    /**
     * @var CompletionListener
     */
    private $completionListener;

    /**
     * @var float
     */
    private $deliveryDelay = PsrMessage::DEFAULT_DELIVERY_DELAY;

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
     *
     * @param PheanstalkDestination $destination
     * @param PheanstalkMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, PheanstalkDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, PheanstalkMessage::class);

        $rawMessage = json_encode($message);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'Could not encode value into json. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        $this->pheanstalk->useTube($destination->getName())->put(
            $rawMessage,
            $message->getPriority(),
            $message->getDelay(),
            $message->getTimeToRun()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return $this->deliveryDelay;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        $this->deliveryDelay = $deliveryDelay;
    }
}
