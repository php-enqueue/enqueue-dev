<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class TestProcessor implements Processor, TopicSubscriberInterface
{
    public const TOPIC = 'test-topic';

    /**
     * @var Message
     */
    public $message;

    public function process(Message $message, Context $context)
    {
        $this->message = $message;

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::TOPIC];
    }
}
