<?php
namespace Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\TopicSubscriberInterface;

class InvalidTopicSubscriber implements TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [12345];
    }
}
