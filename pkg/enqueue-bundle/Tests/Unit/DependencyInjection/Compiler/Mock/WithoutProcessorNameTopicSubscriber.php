<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\TopicSubscriberInterface;

class WithoutProcessorNameTopicSubscriber implements TopicSubscriberInterface
{
    public static function getSubscribedTopics()
    {
        return [
            'without-processor-name' => [
                'queueName' => 'a_queue_name',
                'queueNameHardcoded' => true,
            ],
        ];
    }
}
