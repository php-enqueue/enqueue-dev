<?php

namespace Enqueue\Tests\Symfony\Client\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Symfony\Client\DependencyInjection\BuildTopicSubscriberRoutesPass;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildTopicSubscriberRoutesPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPassInterface()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildTopicSubscriberRoutesPass::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(BuildTopicSubscriberRoutesPass::class);
    }

    public function testCouldBeConstructedWithName()
    {
        $pass = new BuildTopicSubscriberRoutesPass('aName');

        $this->assertAttributeSame('aName', 'name', $pass);
    }

    public function testThrowIfNameEmptyOnConstruct()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name could not be empty.');
        new BuildTopicSubscriberRoutesPass('');
    }

    public function testShouldDoNothingIfRouteCollectionServiceIsNotRegistered()
    {
        $pass = new BuildTopicSubscriberRoutesPass('aName');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowIfTaggedProcessorIsBuiltByFactory()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection', RouteCollection::class)
            ->addArgument([])
        ;
        $container->register('aProcessor', PsrProcessor::class)
            ->setFactory('foo')
            ->addTag('enqueue.topic_subscriber')
        ;

        $pass = new BuildTopicSubscriberRoutesPass('aName');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The topic subscriber tag could not be applied to a service created by factory.');
        $pass->process($container);
    }

    public function testShouldRegisterProcessorWithMatchedName()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($this->createTopicSubscriberProcessor()))
            ->addTag('enqueue.topic_subscriber', ['client' => 'foo'])
        ;
        $container->register('aProcessor', get_class($this->createTopicSubscriberProcessor()))
            ->addTag('enqueue.topic_subscriber', ['client' => 'bar'])
        ;

        $pass = new BuildTopicSubscriberRoutesPass('foo');

        $pass->process($container);

        $this->assertInternalType('array', $routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));
    }

    public function testShouldRegisterProcessorWithoutNameToDefaultClient()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($this->createTopicSubscriberProcessor()))
            ->addTag('enqueue.topic_subscriber')
        ;
        $container->register('aProcessor', get_class($this->createTopicSubscriberProcessor()))
            ->addTag('enqueue.topic_subscriber', ['client' => 'bar'])
        ;

        $pass = new BuildTopicSubscriberRoutesPass('default');

        $pass->process($container);

        $this->assertInternalType('array', $routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));
    }

    public function testShouldRegisterProcessorIfClientNameEqualsAll()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($this->createTopicSubscriberProcessor()))
            ->addTag('enqueue.topic_subscriber', ['client' => 'all'])
        ;
        $container->register('aProcessor', get_class($this->createTopicSubscriberProcessor()))
            ->addTag('enqueue.topic_subscriber', ['client' => 'bar'])
        ;

        $pass = new BuildTopicSubscriberRoutesPass('default');

        $pass->process($container);

        $this->assertInternalType('array', $routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));
    }

    public function testShouldRegisterProcessorIfTopicsIsString()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createTopicSubscriberProcessor('fooTopic');

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.topic_subscriber')
        ;

        $pass = new BuildTopicSubscriberRoutesPass('default');
        $pass->process($container);

        $this->assertInternalType('array', $routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'fooTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testThrowIfTopicSubscriberReturnsNothing()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createTopicSubscriberProcessor(null);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.topic_subscriber')
        ;

        $pass = new BuildTopicSubscriberRoutesPass('default');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic subscriber must return something.');
        $pass->process($container);
    }

    public function testShouldRegisterProcessorIfTopicsAreStrings()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createTopicSubscriberProcessor(['fooTopic', 'barTopic']);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.topic_subscriber')
        ;

        $pass = new BuildTopicSubscriberRoutesPass('default');
        $pass->process($container);

        $this->assertInternalType('array', $routeCollection->getArgument(0));
        $this->assertCount(2, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'fooTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
                [
                    'source' => 'barTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testShouldRegisterProcessorIfTopicsAreParamArrays()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createTopicSubscriberProcessor([
            ['topic' => 'fooTopic', 'processor' => 'aCustomFooProcessorName', 'anOption' => 'aFooVal'],
            ['topic' => 'barTopic', 'processor' => 'aCustomBarProcessorName', 'anOption' => 'aBarVal'],
        ]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.topic_subscriber')
        ;

        $pass = new BuildTopicSubscriberRoutesPass('default');
        $pass->process($container);

        $this->assertInternalType('array', $routeCollection->getArgument(0));
        $this->assertCount(2, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'fooTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aCustomFooProcessorName',
                    'processor_service_id' => 'aFooProcessor',
                    'anOption' => 'aFooVal',
                ],
                [
                    'source' => 'barTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aCustomBarProcessorName',
                    'processor_service_id' => 'aFooProcessor',
                    'anOption' => 'aBarVal',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testThrowIfTopicSubscriberParamsInvalid()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createTopicSubscriberProcessor(['fooBar', true]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.topic_subscriber')
        ;

        $pass = new BuildTopicSubscriberRoutesPass('default');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic subscriber configuration is invalid');
        $pass->process($container);
    }

    public function testShouldMergeExtractedRoutesWithAlreadySetInCollection()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([
            (new Route('aTopic', Route::TOPIC, 'aProcessor'))->toArray(),
            (new Route('aCommand', Route::COMMAND, 'aProcessor'))->toArray(),
        ]);

        $processor = $this->createTopicSubscriberProcessor(['fooTopic']);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.topic_subscriber')
        ;

        $pass = new BuildTopicSubscriberRoutesPass('default');
        $pass->process($container);

        $this->assertInternalType('array', $routeCollection->getArgument(0));
        $this->assertCount(3, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'aTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aProcessor',
                ],
                [
                    'source' => 'aCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aProcessor',
                ],
                [
                    'source' => 'fooTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    private function createTopicSubscriberProcessor($topicSubscriberReturns = ['aTopic'])
    {
        $processor = new class() implements PsrProcessor, TopicSubscriberInterface {
            public static $return;

            public function process(PsrMessage $message, PsrContext $context)
            {
            }

            public static function getSubscribedTopics()
            {
                return static::$return;
            }
        };

        $processor::$return = $topicSubscriberReturns;

        return $processor;
    }
}
