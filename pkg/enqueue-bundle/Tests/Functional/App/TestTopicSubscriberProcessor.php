<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class TestTopicSubscriberProcessor implements PsrProcessor, TopicSubscriberInterface
{
    public $calls = [];

    public function process(PsrMessage $message, PsrContext $context)
    {
        $this->calls[] = $message;

        return Result::reply(
            $context->createMessage($message->getBody().'Reply')
        );
    }

    public static function getSubscribedTopics()
    {
        return 'theTopic';
    }
}
