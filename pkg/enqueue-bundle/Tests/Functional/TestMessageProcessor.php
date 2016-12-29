<?php
namespace Enqueue\EnqueueBundle\Tests\Functional;

use Enqueue\Psr\Context;
use Enqueue\Psr\Message;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Consumption\Result;

class TestMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const TOPIC = 'test-topic';

    /**
     * @var Message
     */
    public $message;

    public function process(Message $message, Context $context)
    {
        $this->message = $message;

        return Result::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::TOPIC];
    }
}
