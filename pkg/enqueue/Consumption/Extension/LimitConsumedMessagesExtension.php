<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class LimitConsumedMessagesExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var int
     */
    protected $messageLimit;

    /**
     * @var int
     */
    protected $messageConsumed;

    /**
     * @param int $messageLimit
     */
    public function __construct($messageLimit)
    {
        if (false == is_int($messageLimit)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected message limit is int but got: "%s"',
                is_object($messageLimit) ? get_class($messageLimit) : gettype($messageLimit)
            ));
        }

        $this->messageLimit = $messageLimit;
        $this->messageConsumed = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        // this is added here to handle an edge case. when a user sets zero as limit.
        $this->checkMessageLimit($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        ++$this->messageConsumed;

        $this->checkMessageLimit($context);
    }

    /**
     * @param Context $context
     */
    protected function checkMessageLimit(Context $context)
    {
        if ($this->messageConsumed >= $this->messageLimit) {
            $context->getLogger()->debug(sprintf(
                '[LimitConsumedMessagesExtension] Message consumption is interrupted since the message limit reached.'.
                ' limit: "%s"',
                $this->messageLimit
            ));

            $context->setExecutionInterrupted(true);
        }
    }
}
