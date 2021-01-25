<?php

namespace Enqueue\AsyncEventDispatcher\Tests;

use Enqueue\AsyncEventDispatcher\ContainerAwareRegistry;
use Enqueue\AsyncEventDispatcher\EventTransformer;
use Enqueue\AsyncEventDispatcher\Registry;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class ContainerAwareRegistryTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementRegistryInterface()
    {
        $this->assertClassImplements(Registry::class, ContainerAwareRegistry::class);
    }

    public function testCouldBeConstructedWithEventsMapAndTransformersMapAsArguments()
    {
        new ContainerAwareRegistry([], []);
    }

    public function testShouldSetContainerToContainerProperty()
    {
        $container = new Container();

        $registry = new ContainerAwareRegistry([], []);

        $registry->setContainer($container);

        $this->assertAttributeSame($container, 'container', $registry);
    }

    public function testShouldAllowGetTransportNameByEventName()
    {
        $registry = new ContainerAwareRegistry([
                'fooEvent' => 'fooTrans',
        ], []);

        $this->assertEquals('fooTrans', $registry->getTransformerNameForEvent('fooEvent'));
    }

    public function testShouldAllowDefineTransportNameAsRegExpPattern()
    {
        $registry = new ContainerAwareRegistry([
            '/.*/' => 'fooRegExpTrans',
            'fooEvent' => 'fooTrans',
        ], []);

        // guard
        $this->assertEquals('fooTrans', $registry->getTransformerNameForEvent('fooEvent'));

        $this->assertEquals('fooRegExpTrans', $registry->getTransformerNameForEvent('fooRegExpEvent'));
    }

    public function testThrowIfNotSupportedEventGiven()
    {
        $registry = new ContainerAwareRegistry([
            'fooEvent' => 'fooTrans',
        ], []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no transformer registered for the given event fooNotSupportedEvent');
        $registry->getTransformerNameForEvent('fooNotSupportedEvent');
    }

    public function testThrowIfThereIsNoRegisteredTransformerWithSuchName()
    {
        $registry = new ContainerAwareRegistry([], [
            'fooTrans' => 'foo_trans_id',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no transformer named fooNotRegisteredName');
        $registry->getTransformer('fooNotRegisteredName');
    }

    public function testThrowIfContainerReturnsServiceNotInstanceOfEventTransformer()
    {
        $container = new Container();
        $container->set('foo_trans_id', new \stdClass());

        $registry = new ContainerAwareRegistry([], [
            'fooTrans' => 'foo_trans_id',
        ]);
        $registry->setContainer($container);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The container must return instance of Enqueue\AsyncEventDispatcher\EventTransformer but got stdClass');
        $registry->getTransformer('fooTrans');
    }

    public function testShouldReturnEventTransformer()
    {
        $eventTransformerMock = $this->createMock(EventTransformer::class);

        $container = new Container();
        $container->set('foo_trans_id', $eventTransformerMock);

        $registry = new ContainerAwareRegistry([], [
            'fooTrans' => 'foo_trans_id',
        ]);
        $registry->setContainer($container);

        $this->assertSame($eventTransformerMock, $registry->getTransformer('fooTrans'));
    }
}
