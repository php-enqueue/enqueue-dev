<?php

namespace Enqueue\Tests\Client\Meta;

use Enqueue\Client\Meta\TopicMeta;
use Enqueue\Client\Meta\TopicMetaRegistry;
use PHPUnit\Framework\TestCase;

class TopicMetaRegistryTest extends TestCase
{
    public function testCouldBeConstructedWithTopics()
    {
        $topics = [
            'aTopicName' => [],
            'anotherTopicName' => [],
        ];

        $registry = new TopicMetaRegistry($topics);

        $this->assertAttributeEquals($topics, 'meta', $registry);
    }

    public function testShouldAllowAddTopicMetaUsingAddMethod()
    {
        $registry = new TopicMetaRegistry([]);

        $registry->add('theFooTopicName', 'aDescription');
        $registry->add('theBarTopicName');

        $this->assertAttributeSame([
            'theFooTopicName' => [
                'description' => 'aDescription',
                'processors' => [],
            ],
            'theBarTopicName' => [
                'description' => null,
                'processors' => [],
            ],
        ], 'meta', $registry);
    }

    public function testShouldAllowAddSubscriber()
    {
        $registry = new TopicMetaRegistry([]);

        $registry->addProcessor('theFooTopicName', 'theFooProcessorName');
        $registry->addProcessor('theFooTopicName', 'theBarProcessorName');
        $registry->addProcessor('theBarTopicName', 'theBazProcessorName');

        $this->assertAttributeSame([
            'theFooTopicName' => [
                'description' => null,
                'processors' => ['theFooProcessorName', 'theBarProcessorName'],
            ],
            'theBarTopicName' => [
                'description' => null,
                'processors' => ['theBazProcessorName'],
            ],
        ], 'meta', $registry);
    }

    public function testThrowIfThereIsNotMetaForRequestedTopicName()
    {
        $registry = new TopicMetaRegistry([]);

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'The topic meta not found. Requested name `aName`'
        );
        $registry->getTopicMeta('aName');
    }

    public function testShouldAllowGetTopicByNameWithDefaultInfo()
    {
        $topics = [
            'theTopicName' => [],
        ];

        $registry = new TopicMetaRegistry($topics);

        $topic = $registry->getTopicMeta('theTopicName');
        $this->assertInstanceOf(TopicMeta::class, $topic);
        $this->assertSame('theTopicName', $topic->getName());
        $this->assertSame('', $topic->getDescription());
        $this->assertSame([], $topic->getProcessors());
    }

    public function testShouldAllowGetTopicByNameWithCustomInfo()
    {
        $topics = [
            'theTopicName' => ['description' => 'theDescription', 'processors' => ['theSubscriber']],
        ];

        $registry = new TopicMetaRegistry($topics);

        $topic = $registry->getTopicMeta('theTopicName');
        $this->assertInstanceOf(TopicMeta::class, $topic);
        $this->assertSame('theTopicName', $topic->getName());
        $this->assertSame('theDescription', $topic->getDescription());
        $this->assertSame(['theSubscriber'], $topic->getProcessors());
    }

    public function testShouldAllowGetAllTopics()
    {
        $topics = [
            'fooTopicName' => [],
            'barTopicName' => [],
        ];

        $registry = new TopicMetaRegistry($topics);

        $topics = $registry->getTopicsMeta();
        $this->assertInstanceOf(\Generator::class, $topics);

        $topics = iterator_to_array($topics);
        /* @var TopicMeta[] $topics */

        $this->assertContainsOnly(TopicMeta::class, $topics);
        $this->assertCount(2, $topics);

        $this->assertSame('fooTopicName', $topics[0]->getName());
        $this->assertSame('barTopicName', $topics[1]->getName());
    }
}
