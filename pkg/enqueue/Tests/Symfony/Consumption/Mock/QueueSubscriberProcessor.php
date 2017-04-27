<?php
namespace Enqueue\Tests\Symfony\Consumption\Mock;

use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;

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
