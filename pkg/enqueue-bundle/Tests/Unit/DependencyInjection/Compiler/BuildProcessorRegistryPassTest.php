<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildProcessorRegistryPass;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\EmptyCommandSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidCommandSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyCommandNameSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameCommandSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\WithoutProcessorNameTopicSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildProcessorRegistryPassTest extends TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildProcessorRegistryPass();
    }

    public function testShouldBuildRouteRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'topic',
            'processorName' => 'processor-name',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'processor-name' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testThrowIfProcessorClassNameCouldNotBeFound()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition('notExistingClass');
        $processor->addTag('enqueue.client.processor', [
            'processorName' => 'processor',
        ]);
        $container->setDefinition('processor', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The class "notExistingClass" could not be found.');
        $pass->process($container);
    }

    public function testShouldThrowExceptionIfTopicNameIsNotSet()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();

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

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'processor-id' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldBuildRouteFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(OnlyTopicNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'processor-id' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldBuildRouteFromWithoutProcessorNameTopicSubscriber()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(WithoutProcessorNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'processor-id' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldBuildRouteFromSubscriberIfProcessorNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(ProcessorNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'subscriber-processor-name' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic subscriber configuration is invalid. "[12345]"');

        $container = $this->createContainerBuilder();

        $processor = new Definition(InvalidTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);
    }

    public function testShouldBuildRouteFromOnlyNameCommandSubscriber()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(OnlyCommandNameSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'the-command-name' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldBuildRouteFromProcessorNameCommandSubscriber()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(ProcessorNameCommandSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'the-command-name' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldThrowExceptionWhenProcessorNameEmpty()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(EmptyCommandSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The processor name (it is also the command name) must not be empty.');

        $pass->process($container);
    }

    public function testShouldThrowExceptionWhenCommandSubscriberConfigurationIsInvalid()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(InvalidCommandSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.processor_registry', $processorRegistry);

        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Command subscriber configuration is invalid. "12345"');

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
