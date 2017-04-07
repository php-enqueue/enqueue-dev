<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;

class TestProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const TOPIC = 'test-topic';

    /**
     * @var PsrMessage
     */
    public $message;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $this->message = $message;

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::TOPIC];
    }
}
