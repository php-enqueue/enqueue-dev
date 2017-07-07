<?php

namespace Enqueue\Tests\Symfony\Consumption\Mock;

use Enqueue\Consumption\QueueSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class QueueSubscriberProcessor implements PsrProcessor, QueueSubscriberInterface
{
    public function process(PsrMessage $message, PsrContext $context)
    {
    }

    public static function getSubscribedQueues()
    {
        return ['fooSubscribedQueues', 'barSubscribedQueues'];
    }
}
