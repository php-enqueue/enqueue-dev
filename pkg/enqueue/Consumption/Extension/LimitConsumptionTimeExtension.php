<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class LimitConsumptionTimeExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var \DateTime
     */
    protected $timeLimit;

    /**
     * @param \DateTime $timeLimit
     */
    public function __construct(\DateTime $timeLimit)
    {
        $this->timeLimit = $timeLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        $this->checkTime($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $this->checkTime($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $this->checkTime($context);
    }

    /**
     * @param Context $context
     */
    protected function checkTime(Context $context)
    {
        $now = new \DateTime();
        if ($now >= $this->timeLimit) {
            $context->getLogger()->debug(sprintf(
                '[LimitConsumptionTimeExtension] Execution interrupted as limit time has passed.'.
                ' now: "%s", time-limit: "%s"',
                $now->format(DATE_ISO8601),
                $this->timeLimit->format(DATE_ISO8601)
            ));

            $context->setExecutionInterrupted(true);
        }
    }
}
