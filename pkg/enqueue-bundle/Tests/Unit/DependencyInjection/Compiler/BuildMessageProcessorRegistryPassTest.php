<?php
namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildMessageProcessorRegistryPassTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildMessageProcessorRegistryPass();
    }

    public function testShouldBuildRouteRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.message_processor', [
            'topicName' => 'topic',
            'processorName' => 'processor-name',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
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
        $processor->addTag('enqueue.client.message_processor', [
            'processorName' => 'processor',
        ]);
        $container->setDefinition('processor', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The class "notExistingClass" could not be found.');
        $pass->process($container);
    }

    public function testShouldThrowExceptionIfTopicNameIsNotSet()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.message_processor');
        $container->setDefinition('processor', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name is not set on message processor tag but it is required.');
        $pass->process($container);
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.message_processor', [
            'topicName' => 'topic',
        ]);
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
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
        $processor->addTag('enqueue.client.message_processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
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
        $processor->addTag('enqueue.client.message_processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
        $pass->process($container);

        $expectedValue = [
            'subscriber-processor-name' => 'processor-id',
        ];

        $this->assertEquals($expectedValue, $processorRegistry->getArgument(0));
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
    {
        $this->setExpectedException(\LogicException::class, 'Topic subscriber configuration is invalid. "[12345]"');

        $container = $this->createContainerBuilder();

        $processor = new Definition(InvalidTopicSubscriber::class);
        $processor->addTag('enqueue.client.message_processor');
        $container->setDefinition('processor-id', $processor);

        $processorRegistry = new Definition();
        $processorRegistry->setArguments([]);
        $container->setDefinition('enqueue.client.message_processor_registry', $processorRegistry);

        $pass = new BuildMessageProcessorRegistryPass();
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
