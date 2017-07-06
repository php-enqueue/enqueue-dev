<?php

namespace Enqueue\AsyncEventDispatcher\Tests;

use Enqueue\AsyncEventDispatcher\EventTransformer;
use Enqueue\AsyncEventDispatcher\Registry;
use Enqueue\AsyncEventDispatcher\SimpleRegistry;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class SimpleRegistryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementRegistryInterface()
    {
        $this->assertClassImplements(Registry::class, SimpleRegistry::class);
    }

    public function testCouldBeConstructedWithEventsMapAndTransformersMapAsArguments()
    {
        new SimpleRegistry([], []);
    }

    public function testShouldAllowGetTransportNameByEventName()
    {
        $registry = new SimpleRegistry([
                'fooEvent' => 'fooTrans',
        ], []);

        $this->assertEquals('fooTrans', $registry->getTransformerNameForEvent('fooEvent'));
    }

    public function testShouldAllowDefineTransportNameAsRegExpPattern()
    {
        $registry = new SimpleRegistry([
            '/.*/' => 'fooRegExpTrans',
            'fooEvent' => 'fooTrans',
        ], []);

        // guard
        $this->assertEquals('fooTrans', $registry->getTransformerNameForEvent('fooEvent'));

        $this->assertEquals('fooRegExpTrans', $registry->getTransformerNameForEvent('fooRegExpEvent'));
    }

    public function testThrowIfNotSupportedEventGiven()
    {
        $registry = new SimpleRegistry([
            'fooEvent' => 'fooTrans',
        ], []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no transformer registered for the given event fooNotSupportedEvent');
        $registry->getTransformerNameForEvent('fooNotSupportedEvent');
    }

    public function testThrowIfThereIsNoRegisteredTransformerWithSuchName()
    {
        $registry = new SimpleRegistry([], [
            'fooTrans' => 'foo_trans_id',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no transformer named fooNotRegisteredName');
        $registry->getTransformer('fooNotRegisteredName');
    }

    public function testThrowIfObjectAssocWithTransportNameNotInstanceOfEventTransformer()
    {
        $container = new Container();
        $container->set('foo_trans_id', new \stdClass());

        $registry = new SimpleRegistry([], [
            'fooTrans' => new \stdClass(),
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The container must return instance of Enqueue\AsyncEventDispatcher\EventTransformer but got stdClass');
        $registry->getTransformer('fooTrans');
    }

    public function testShouldReturnEventTransformer()
    {
        $eventTransformerMock = $this->createMock(EventTransformer::class);

        $container = new Container();
        $container->set('foo_trans_id', $eventTransformerMock);

        $registry = new SimpleRegistry([], [
            'fooTrans' => $eventTransformerMock,
        ]);

        $this->assertSame($eventTransformerMock, $registry->getTransformer('fooTrans'));
    }
}
