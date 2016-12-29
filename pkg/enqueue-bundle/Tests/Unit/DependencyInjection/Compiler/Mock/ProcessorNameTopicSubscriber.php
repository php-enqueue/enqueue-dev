<?php
namespace Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\TopicSubscriberInterface;

class ProcessorNameTopicSubscriber implements TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [
            'topic-subscriber-name' => [
                'processorName' => 'subscriber-processor-name',
            ],
        ];
    }
}
