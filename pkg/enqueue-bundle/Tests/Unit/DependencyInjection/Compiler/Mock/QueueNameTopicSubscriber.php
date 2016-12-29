<?php
namespace Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\TopicSubscriberInterface;

class QueueNameTopicSubscriber implements TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [
            'topic-subscriber-name' => [
                'queueName' => 'subscriber-queue-name',
            ],
        ];
    }
}
