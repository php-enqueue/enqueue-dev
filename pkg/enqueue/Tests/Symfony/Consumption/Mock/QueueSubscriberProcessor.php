<?php

namespace Enqueue\Tests\Symfony\Consumption\Mock;

use Enqueue\Consumption\QueueSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;

class QueueSubscriberProcessor implements Processor, QueueSubscriberInterface
{
    public function process(InteropMessage $message, Context $context)
    {
    }

    public static function getSubscribedQueues()
    {
        return ['fooSubscribedQueues', 'barSubscribedQueues'];
    }
}
