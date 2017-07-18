<?php

namespace Enqueue\LaravelQueue\Tests;

use Enqueue\LaravelQueue\Connector;
use Enqueue\LaravelQueue\EnqueueServiceProvider;
use Enqueue\Test\ClassExtensionTrait;
use Illuminate\Container\Container;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

class EnqueueServiceProviderTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldExtendsServiceProvider()
    {
        $this->assertClassExtends(ServiceProvider::class, EnqueueServiceProvider::class);
    }

    public function testShouldBeConstructedWithContainerAsFirstArgument()
    {
        new EnqueueServiceProvider(new Container());
    }

    public function testShouldAddEnqueueServiceProviderOnBootCall()
    {
        $queueManagerMock = $this->createMock(QueueManager::class);
        $queueManagerMock
            ->expects($this->once())
            ->method('addConnector')
            ->with('enqueue', $this->isInstanceOf(\Closure::class))
            ->willReturnCallback(function ($name, \Closure $closure) {
                $this->assertInstanceOf(Connector::class, call_user_func($closure));
            });

        $container = new Container();
        $container['queue'] = $queueManagerMock;

        $provider = new EnqueueServiceProvider($container);

        $provider->boot();
    }
}
