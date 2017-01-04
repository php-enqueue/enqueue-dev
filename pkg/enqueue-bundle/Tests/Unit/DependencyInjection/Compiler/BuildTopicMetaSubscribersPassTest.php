<?php
namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildTopicMetaSubscribersPassTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildTopicMetaSubscribersPass();
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndEmptyRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'topic',
            'processorName' => 'processor-name',
        ]);
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'topic' => ['processors' => ['processor-name']],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testThrowIfProcessorClassNameCouldNotBeFound()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition('notExistingClass');
        $processor->addTag('enqueue.client.processor', [
            'processorName' => 'processor',
        ]);
        $container->setDefinition('processor', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The class "notExistingClass" could not be found.');
        $pass->process($container);
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndSameMetaInRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'topic',
            'processorName' => 'barProcessorName',
        ]);
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[
            'topic' => ['description' => 'aDescription', 'processors' => ['fooProcessorName']],
        ]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'topic' => [
                'description' => 'aDescription',
                'processors' => ['fooProcessorName', 'barProcessorName'],
            ],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildTopicMetaSubscribersForOneTagAndSameMetaInPlusAnotherRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'barProcessorName',
        ]);
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[
            'fooTopic' => ['description' => 'aDescription', 'processors' => ['fooProcessorName']],
            'barTopic' => ['description' => 'aBarDescription'],
        ]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'fooTopic' => [
                'description' => 'aDescription',
                'processors' => ['fooProcessorName', 'barProcessorName'],
            ],
            'barTopic' => ['description' => 'aBarDescription'],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildTopicMetaSubscribersForTwoTagAndEmptyRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'fooProcessorName',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'barProcessorName',
        ]);
        $container->setDefinition('another-processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'fooTopic' => [
                'processors' => ['fooProcessorName', 'barProcessorName'],
            ],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildTopicMetaSubscribersForTwoTagSameMetaRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'fooProcessorName',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'fooTopic',
            'processorName' => 'barProcessorName',
        ]);
        $container->setDefinition('another-processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[
            'fooTopic' => ['description' => 'aDescription', 'processors' => ['bazProcessorName']],
        ]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'fooTopic' => [
                'description' => 'aDescription',
                'processors' => ['bazProcessorName', 'fooProcessorName', 'barProcessorName'],
            ],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testThrowIfTopicNameNotSetOnTagAsAttribute()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', []);
        $container->setDefinition('processor', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name is not set on message processor tag but it is required.');
        $pass->process($container);
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'topic',
        ]);
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'topic' => ['processors' => ['processor-id']],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildMetaFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(OnlyTopicNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'topic-subscriber-name' => ['processors' => ['processor-id']],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldBuildMetaFromSubscriberIfProcessorNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(ProcessorNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);

        $expectedValue = [
            'topic-subscriber-name' => ['processors' => ['subscriber-processor-name']],
        ];

        $this->assertEquals($expectedValue, $topicMetaRegistry->getArgument(0));
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
    {
        $this->setExpectedException(\LogicException::class, 'Topic subscriber configuration is invalid. "[12345]"');

        $container = $this->createContainerBuilder();

        $processor = new Definition(InvalidTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $topicMetaRegistry = new Definition();
        $topicMetaRegistry->setArguments([[]]);
        $container->setDefinition('enqueue.client.meta.topic_meta_registry', $topicMetaRegistry);

        $pass = new BuildTopicMetaSubscribersPass();
        $pass->process($container);
    }

    /**
     * @return ContainerBuilder
     */
    private function createContainerBuilder()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.client.default_queue_name', 'aDefaultQueueName');

        return $container;
    }
}
