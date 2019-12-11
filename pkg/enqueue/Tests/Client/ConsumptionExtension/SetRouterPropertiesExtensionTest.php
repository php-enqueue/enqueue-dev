<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\SetRouterPropertiesExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SetRouterPropertiesExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageReceivedExtensionInterface()
    {
        $this->assertClassImplements(MessageReceivedExtensionInterface::class, SetRouterPropertiesExtension::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SetRouterPropertiesExtension($this->createDriverMock());
    }

    public function testShouldSetRouterProcessorPropertyIfNotSetAndOnRouterQueue()
    {
        $config = Config::create('test', '.', '', '', 'router-queue', '', 'router-processor-name');
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
        $message->setProperty(Config::TOPIC, 'aTopic');

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub(new NullQueue('test.router-queue')),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $extension = new SetRouterPropertiesExtension($driver);
        $extension->onMessageReceived($messageReceived);

        $this->assertEquals([
            Config::PROCESSOR => 'router-processor-name',
            Config::TOPIC => 'aTopic',
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
        $message->setProperty(Config::TOPIC, 'aTopic');

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub(new NullQueue('test.another-queue')),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $extension = new SetRouterPropertiesExtension($driver);
        $extension->onMessageReceived($messageReceived);

        $this->assertEquals([
            Config::TOPIC => 'aTopic',
        ], $message->getProperties());
    }

    public function testShouldNotSetAnyPropertyIfProcessorNamePropertyAlreadySet()
    {
        $driver = $this->createDriverMock();
        $driver
            ->expects($this->never())
            ->method('getConfig')
        ;

        $message = new NullMessage();
        $message->setProperty(Config::PROCESSOR, 'non-router-processor');

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub(null),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $extension = new SetRouterPropertiesExtension($driver);
        $extension->onMessageReceived($messageReceived);

        $this->assertEquals([
            'enqueue.processor' => 'non-router-processor',
        ], $message->getProperties());
    }

    public function testShouldSkipMessagesWithoutTopicPropertySet()
    {
        $driver = $this->createDriverMock();
        $driver
            ->expects($this->never())
            ->method('getConfig')
        ;

        $message = new NullMessage();

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub(null),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $extension = new SetRouterPropertiesExtension($driver);
        $extension->onMessageReceived($messageReceived);

        $this->assertEquals([], $message->getProperties());
    }

    /**
     * @return MockObject|InteropContext
     */
    protected function createContextMock(): InteropContext
    {
        return $this->createMock(InteropContext::class);
    }

    /**
     * @return MockObject|DriverInterface
     */
    protected function createDriverMock(): DriverInterface
    {
        return $this->createMock(DriverInterface::class);
    }

    /**
     * @return MockObject
     */
    private function createProcessorMock(): Processor
    {
        return $this->createMock(Processor::class);
    }

    /**
     * @param mixed $queue
     *
     * @return MockObject
     */
    private function createConsumerStub($queue): Consumer
    {
        $consumerMock = $this->createMock(Consumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        return $consumerMock;
    }
}
