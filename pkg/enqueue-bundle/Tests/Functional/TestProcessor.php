<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Psr\Context;
use Enqueue\Psr\Message;
use Enqueue\Psr\Processor;

class TestProcessor implements Processor, TopicSubscriberInterface
{
    const TOPIC = 'test-topic';

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
