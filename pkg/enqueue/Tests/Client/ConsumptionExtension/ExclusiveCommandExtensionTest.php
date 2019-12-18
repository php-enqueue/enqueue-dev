<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\ExclusiveCommandExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
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

class ExclusiveCommandExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageReceivedExtensionInterface()
    {
        $this->assertClassImplements(MessageReceivedExtensionInterface::class, ExclusiveCommandExtension::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(ExclusiveCommandExtension::class);
    }

    public function testCouldBeConstructedWithDriverAsFirstArgument()
    {
        new ExclusiveCommandExtension($this->createDriverStub());
    }

    public function testShouldDoNothingIfMessageHasTopicPropertySetOnPreReceive()
    {
        $message = new NullMessage();
        $message->setProperty(Config::TOPIC, 'aTopic');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('createQueue')
        ;

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub(null),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $extension = new ExclusiveCommandExtension($driver);

        $extension->onMessageReceived($messageReceived);

        self::assertNull($messageReceived->getResult());

        $this->assertEquals([
            Config::TOPIC => 'aTopic',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfMessageHasCommandPropertySetOnPreReceive()
    {
        $message = new NullMessage();
        $message->setProperty(Config::COMMAND, 'aCommand');

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub(null),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('createQueue')
        ;

        $extension = new ExclusiveCommandExtension($driver);

        $extension->onMessageReceived($messageReceived);

        self::assertNull($messageReceived->getResult());

        $this->assertEquals([
            Config::COMMAND => 'aCommand',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfMessageHasProcessorPropertySetOnPreReceive()
    {
        $message = new NullMessage();
        $message->setProperty(Config::PROCESSOR, 'aProcessor');

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub(null),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('createQueue')
        ;

        $extension = new ExclusiveCommandExtension($driver);

        $extension->onMessageReceived($messageReceived);

        self::assertNull($messageReceived->getResult());

        $this->assertEquals([
            Config::PROCESSOR => 'aProcessor',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfCurrentQueueHasNoExclusiveProcessor()
    {
        $message = new NullMessage();
        $queue = new NullQueue('aBarQueueName');

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub($queue),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $extension = new ExclusiveCommandExtension($this->createDriverStub(new RouteCollection([])));

        $extension->onMessageReceived($messageReceived);

        self::assertNull($messageReceived->getResult());

        $this->assertEquals([], $message->getProperties());
    }

    public function testShouldSetCommandPropertiesIfCurrentQueueHasExclusiveCommandProcessor()
    {
        $message = new NullMessage();
        $queue = new NullQueue('fooQueue');

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub($queue),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $routeCollection = new RouteCollection([
            new Route('fooCommand', Route::COMMAND, 'theFooProcessor', [
                'exclusive' => true,
                'queue' => 'fooQueue',
            ]),
            new Route('barCommand', Route::COMMAND, 'theFooProcessor', [
                'exclusive' => true,
                'queue' => 'barQueue',
            ]),
        ]);

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->any())
            ->method('createRouteQueue')
            ->with($this->isInstanceOf(Route::class))
            ->willReturnCallback(function (Route $route) {
                return new NullQueue($route->getQueue());
            })
        ;

        $extension = new ExclusiveCommandExtension($driver);
        $extension->onMessageReceived($messageReceived);

        self::assertNull($messageReceived->getResult());

        $this->assertEquals([
            Config::PROCESSOR => 'theFooProcessor',
            Config::COMMAND => 'fooCommand',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfAnotherQueue()
    {
        $message = new NullMessage();
        $queue = new NullQueue('barQueue');

        $messageReceived = new MessageReceived(
            $this->createContextMock(),
            $this->createConsumerStub($queue),
            $message,
            $this->createProcessorMock(),
            1,
            new NullLogger()
        );

        $routeCollection = new RouteCollection([
            new Route('fooCommand', Route::COMMAND, 'theFooProcessor', [
                'exclusive' => true,
                'queue' => 'fooQueue',
            ]),
            new Route('barCommand', Route::COMMAND, 'theFooProcessor', [
                'exclusive' => false,
                'queue' => 'barQueue',
            ]),
        ]);

        $driver = $this->createDriverStub($routeCollection);
        $driver
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function (string $queueName) {
                return new NullQueue($queueName);
            })
        ;

        $extension = new ExclusiveCommandExtension($driver);
        $extension->onMessageReceived($messageReceived);

        self::assertNull($messageReceived->getResult());

        $this->assertEquals([], $message->getProperties());
    }

    /**
     * @return MockObject
     */
    private function createDriverStub(RouteCollection $routeCollection = null): DriverInterface
    {
        $driver = $this->createMock(DriverInterface::class);
        $driver
            ->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($routeCollection)
        ;

        return $driver;
    }

    /**
     * @return MockObject
     */
    private function createContextMock(): InteropContext
    {
        return $this->createMock(InteropContext::class);
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
