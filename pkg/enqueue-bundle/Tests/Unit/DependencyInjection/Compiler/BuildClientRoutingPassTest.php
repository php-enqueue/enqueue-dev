<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientRoutingPass;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyCommandNameSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameCommandSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\QueueNameTopicSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\WithoutProcessorNameTopicSubscriber;
use Enqueue\Client\RouterProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildClientRoutingPassTest extends TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildClientRoutingPass();
    }

    public function testShouldBuildRouteRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'topic',
            'processorName' => 'processor',
            'queueName' => 'queue',
        ]);
        $container->setDefinition('processor', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic' => [
                ['processor', 'queue'],
            ],
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testThrowIfProcessorClassNameCouldNotBeFound()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition('notExistingClass');
        $processor->addTag('enqueue.client.processor', [
            'processorName' => 'processor',
        ]);
        $container->setDefinition('processor', $processor);

        $router = new Definition();
        $router->setArguments([null, []]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();

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

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();

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
            'queueName' => 'queue',
        ]);
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic' => [
                ['processor-service-id', 'queue'],
            ],
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldSetDefaultQueueIfNotSetInTag()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.processor', [
            'topicName' => 'topic',
        ]);
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic' => [
                ['processor-service-id', 'aDefaultQueueName'],
            ],
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldBuildRouteFromSubscriberIfOnlyTopicNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(OnlyTopicNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic-subscriber-name' => [
                ['processor-service-id', 'aDefaultQueueName'],
            ],
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldBuildRouteFromSubscriberIfProcessorNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(ProcessorNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic-subscriber-name' => [
                ['subscriber-processor-name', 'aDefaultQueueName'],
            ],
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldBuildRouteFromSubscriberIfQueueNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(QueueNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic-subscriber-name' => [
                ['processor-service-id', 'subscriber-queue-name'],
            ],
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldBuildRouteFromWithoutProcessorNameTopicSubscriber()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(WithoutProcessorNameTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'without-processor-name' => [
                ['processor-service-id', 'a_queue_name'],
            ],
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(InvalidTopicSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments(['', '']);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic subscriber configuration is invalid. "[12345]"');

        $pass->process($container);
    }

    public function testShouldBuildRouteFromCommandSubscriberIfOnlyCommandNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(OnlyCommandNameSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'the-command-name' => 'aDefaultQueueName',
        ];

        $this->assertEquals([], $router->getArgument(1));
        $this->assertEquals($expectedRoutes, $router->getArgument(2));
    }

    public function testShouldBuildRouteFromCommandSubscriberIfProcessorNameSpecified()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(ProcessorNameCommandSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition(RouterProcessor::class, $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'the-command-name' => 'the-command-queue-name',
        ];

        $this->assertEquals([], $router->getArgument(1));
        $this->assertEquals($expectedRoutes, $router->getArgument(2));
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
