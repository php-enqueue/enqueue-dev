<?php
namespace Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\EnqueueBundle\DependencyInjection\Compiler\BuildClientRoutingPass;
use Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\InvalidTopicSubscriber;
use Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\OnlyTopicNameTopicSubscriber;
use Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\ProcessorNameTopicSubscriber;
use Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock\QueueNameTopicSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildClientRoutingPassTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildClientRoutingPass();
    }

    public function testShouldBuildRouteRegistry()
    {
        $container = $this->createContainerBuilder();

        $processor = new Definition(\stdClass::class);
        $processor->addTag('enqueue.client.message_processor', [
            'topicName' => 'topic',
            'processorName' => 'processor',
            'queueName' => 'queue',
        ]);
        $container->setDefinition('processor', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition('enqueue.client.router_processor', $router);

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
        $processor->addTag('enqueue.client.message_processor', [
            'processorName' => 'processor',
        ]);
        $container->setDefinition('processor', $processor);

        $router = new Definition();
        $router->setArguments([null, []]);
        $container->setDefinition('enqueue.client.router_processor', $router);

        $pass = new BuildClientRoutingPass();

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

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition('enqueue.client.router_processor', $router);

        $pass = new BuildClientRoutingPass();

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
            'queueName' => 'queue',
        ]);
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition('enqueue.client.router_processor', $router);

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
        $processor->addTag('enqueue.client.message_processor', [
            'topicName' => 'topic',
        ]);
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition('enqueue.client.router_processor', $router);

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
        $processor->addTag('enqueue.client.message_processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition('enqueue.client.router_processor', $router);

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
        $processor->addTag('enqueue.client.message_processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition('enqueue.client.router_processor', $router);

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
        $processor->addTag('enqueue.client.message_processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments([null, null, null]);
        $container->setDefinition('enqueue.client.router_processor', $router);

        $pass = new BuildClientRoutingPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic-subscriber-name' => [
                ['processor-service-id', 'subscriber-queue-name'],
            ],
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldThrowExceptionWhenTopicSubscriberConfigurationIsInvalid()
    {
        $this->setExpectedException(\LogicException::class, 'Topic subscriber configuration is invalid. "[12345]"');

        $container = $this->createContainerBuilder();

        $processor = new Definition(InvalidTopicSubscriber::class);
        $processor->addTag('enqueue.client.message_processor');
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments(['', '']);
        $container->setDefinition('enqueue.client.router_processor', $router);

        $pass = new BuildClientRoutingPass();
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
