<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\SetRouterPropertiesExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context as InteropContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SetRouterPropertiesExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, SetRouterPropertiesExtension::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SetRouterPropertiesExtension($this->createDriverMock());
    }

    public function testShouldSetRouterProcessorPropertyIfNotSetAndOnRouterQueue()
    {
        $config = Config::create('test', '', '', 'router-queue', '', 'router-processor-name');
        $queue = new NullQueue('test.router-queue');

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($queue)
        ;

        $message = new NullMessage();

        $context = new Context($this->createContextMock());
        $context->setLogger(new NullLogger());
        $context->setInteropMessage($message);
        $context->setInteropQueue(new NullQueue('test.router-queue'));

        $extension = new SetRouterPropertiesExtension($driver);
        $extension->onPreReceived($context);

        $this->assertEquals([
            'enqueue.processor' => 'router-processor-name',
        ], $message->getProperties());
    }

    public function testShouldNotSetRouterProcessorPropertyIfNotSetAndNotOnRouterQueue()
    {
        $config = Config::create('test', '', '', 'router-queue', '', 'router-processor-name');
        $queue = new NullQueue('test.router-queue');

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $driver
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($queue)
        ;

        $message = new NullMessage();

        $context = new Context($this->createContextMock());
        $context->setInteropMessage($message);
        $context->setInteropQueue(new NullQueue('test.another-queue'));

        $extension = new SetRouterPropertiesExtension($driver);
        $extension->onPreReceived($context);

        $this->assertEquals([], $message->getProperties());
    }

    public function testShouldNotSetAnyPropertyIfProcessorNamePropertyAlreadySet()
    {
        $driver = $this->createDriverMock();
        $driver
            ->expects($this->never())
            ->method('getConfig')
        ;

        $message = new NullMessage();
        $message->setProperty(Config::PROCESSOR_PARAMETER, 'non-router-processor');

        $context = new Context($this->createContextMock());
        $context->setInteropMessage($message);

        $extension = new SetRouterPropertiesExtension($driver);
        $extension->onPreReceived($context);

        $this->assertEquals([
            'enqueue.processor' => 'non-router-processor',
        ], $message->getProperties());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InteropContext
     */
    protected function createContextMock(): InteropContext
    {
        return $this->createMock(InteropContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
