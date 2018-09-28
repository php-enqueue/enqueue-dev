<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\ExclusiveCommandExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\ExtensionInterface as ConsumptionExtensionInterface;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ExclusiveCommandExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumptionExtensionInterface()
    {
        $this->assertClassImplements(ConsumptionExtensionInterface::class, ExclusiveCommandExtension::class);
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
        $message->setProperty(Config::TOPIC_PARAMETER, 'aTopic');

        $context = new Context(new NullContext());
        $context->setInteropMessage($message);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('createQueue')
        ;

        $extension = new ExclusiveCommandExtension($driver);

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([
            'enqueue.topic' => 'aTopic',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfMessageHasCommandPropertySetOnPreReceive()
    {
        $message = new NullMessage();
        $message->setProperty(Config::COMMAND_PARAMETER, 'aCommand');

        $context = new Context(new NullContext());
        $context->setInteropMessage($message);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('createQueue')
        ;

        $extension = new ExclusiveCommandExtension($driver);

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([
            'enqueue.command' => 'aCommand',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfMessageHasProcessorPropertySetOnPreReceive()
    {
        $message = new NullMessage();
        $message->setProperty(Config::PROCESSOR_PARAMETER, 'aProcessor');

        $context = new Context(new NullContext());
        $context->setInteropMessage($message);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('createQueue')
        ;

        $extension = new ExclusiveCommandExtension($driver);

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([
            'enqueue.processor' => 'aProcessor',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfCurrentQueueHasNoExclusiveProcessor()
    {
        $message = new NullMessage();
        $queue = new NullQueue('aBarQueueName');

        $context = new Context(new NullContext());
        $context->setInteropMessage($message);
        $context->setInteropQueue($queue);

        $extension = new ExclusiveCommandExtension($this->createDriverStub(new RouteCollection([])));

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([], $message->getProperties());
    }

    public function testShouldSetCommandPropertiesIfCurrentQueueHasExclusiveCommandProcessor()
    {
        $message = new NullMessage();
        $queue = new NullQueue('fooQueue');

        $context = new Context(new NullContext());
        $context->setInteropMessage($message);
        $context->setInteropQueue($queue);
        $context->setLogger(new NullLogger());

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
            ->method('createQueue')
            ->willReturnCallback(function (string $queueName) {
                return new NullQueue($queueName);
            })
        ;

        $extension = new ExclusiveCommandExtension($driver);
        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([
            'enqueue.processor' => 'theFooProcessor',
            'enqueue.command' => 'fooCommand',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfAnotherQueue()
    {
        $message = new NullMessage();
        $queue = new NullQueue('barQueue');

        $context = new Context(new NullContext());
        $context->setInteropMessage($message);
        $context->setInteropQueue($queue);
        $context->setLogger(new NullLogger());

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
        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([], $message->getProperties());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
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
}
