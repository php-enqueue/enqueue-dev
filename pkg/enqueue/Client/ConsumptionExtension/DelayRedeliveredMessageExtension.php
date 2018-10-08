<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Enqueue\Consumption\Result;

class DelayRedeliveredMessageExtension implements MessageReceivedExtensionInterface
{
    const PROPERTY_REDELIVER_COUNT = 'enqueue.redelivery_count';

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * The number of seconds the message should be delayed.
     *
     * @var int
     */
    private $delay;

    /**
     * @param DriverInterface $driver
     * @param int             $delay  The number of seconds the message should be delayed
     */
    public function __construct(DriverInterface $driver, $delay)
    {
        $this->driver = $driver;
        $this->delay = $delay;
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        $message = $context->getMessage();
        if (false == $message->isRedelivered()) {
            return;
        }
        if (false != $context->getResult()) {
            return;
        }

        $delayedMessage = $this->driver->createClientMessage($message);

        // increment redelivery count
        $redeliveryCount = (int) $delayedMessage->getProperty(self::PROPERTY_REDELIVER_COUNT, 0);
        $delayedMessage->setProperty(self::PROPERTY_REDELIVER_COUNT, $redeliveryCount + 1);

        $delayedMessage->setDelay($this->delay);

        $this->driver->sendToProcessor($delayedMessage);
        $context->getLogger()->debug('[DelayRedeliveredMessageExtension] Send delayed message');

        $context->setResult(Result::reject('A new copy of the message was sent with a delay. The original message is rejected'));
        $context->getLogger()->debug(
            '[DelayRedeliveredMessageExtension] '.
            'Reject redelivered original message by setting reject status to context.'
        );
    }
}
