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

    public function testShouldAllowGetTransportNameByEventName()
    {
        $container = new Container();

        $registry = new ContainerAwareRegistry([
            'fooEvent' => 'fooTrans',
        ], [], $container);

        $this->assertEquals('fooTrans', $registry->getTransformerNameForEvent('fooEvent'));
    }

    public function testShouldAllowDefineTransportNameAsRegExpPattern()
    {
        $container = new Container();

        $registry = new ContainerAwareRegistry([
            '/.*/' => 'fooRegExpTrans',
            'fooEvent' => 'fooTrans',
        ], [], $container);

        // guard
        $this->assertEquals('fooTrans', $registry->getTransformerNameForEvent('fooEvent'));

        $this->assertEquals('fooRegExpTrans', $registry->getTransformerNameForEvent('fooRegExpEvent'));
    }

    public function testThrowIfNotSupportedEventGiven()
    {
        $container = new Container();

        $registry = new ContainerAwareRegistry([
            'fooEvent' => 'fooTrans',
        ], [], $container);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no transformer registered for the given event fooNotSupportedEvent');
        $registry->getTransformerNameForEvent('fooNotSupportedEvent');
    }

    public function testThrowIfThereIsNoRegisteredTransformerWithSuchName()
    {
        $container = new Container();

        $registry = new ContainerAwareRegistry([], [
            'fooTrans' => 'foo_trans_id',
        ], $container);

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
        ], $container);

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
        ], $container);

        $this->assertSame($eventTransformerMock, $registry->getTransformer('fooTrans'));
    }
}
