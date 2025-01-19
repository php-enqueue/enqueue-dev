<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\Driver\GpsDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsMessage;
use Enqueue\Gps\GpsProducer;
use Enqueue\Gps\GpsQueue;
use Enqueue\Gps\GpsTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use Interop\Queue\Topic as InteropTopic;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GpsDriverTest extends TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, GpsDriver::class);
    }

    public function testShouldBeSubClassOfGenericDriver()
    {
        $this->assertClassExtends(GenericDriver::class, GpsDriver::class);
    }

    public function testShouldSetupBroker()
    {
        $routerTopic = new GpsTopic('');
        $routerQueue = new GpsQueue('');

        $processorTopic = new GpsTopic($this->getDefaultQueueTransportName());
        $processorQueue = new GpsQueue($this->getDefaultQueueTransportName());

        $context = $this->createContextMock();
        // setup router
        $context
            ->expects($this->exactly(2))
            ->method('createTopic')
            ->with($this->logicalOr(
                'aprefix.router',
                $this->getDefaultQueueTransportName(),
            ))
            ->willReturnOnConsecutiveCalls($routerTopic, $processorTopic)
        ;
        $context
            ->expects($this->exactly(2))
            ->method('createQueue')
            ->with($this->getDefaultQueueTransportName())
            ->willReturnOnConsecutiveCalls($routerQueue, $processorQueue)
        ;

        $invoked = $this->exactly(2);
        $context
            ->expects($invoked)
            ->method('subscribe')
            ->willReturnCallback(function ($topic, $queue) use ($invoked, $routerTopic, $processorTopic, $routerQueue, $processorQueue) {
                match ($invoked->getInvocationCount()) {
                    1 => $this->assertSame([$routerTopic, $routerQueue], [$topic, $queue]),
                    2 => $this->assertSame([$processorTopic, $processorQueue] , [$topic, $queue]),
                };
            });

        $driver = new GpsDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('aTopic', Route::TOPIC, 'aProcessor'),
            ])
        );

        $driver->setupBroker();
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new GpsDriver(...$args);
    }

    /**
     * @return GpsContext&MockObject
     */
    protected function createContextMock(): Context
    {
        return $this->createMock(GpsContext::class);
    }

    /**
     * @return GpsProducer
     */
    protected function createProducerMock(): InteropProducer
    {
        return $this->createMock(GpsProducer::class);
    }

    /**
     * @return GpsQueue
     */
    protected function createQueue(string $name): InteropQueue
    {
        return new GpsQueue($name);
    }

    /**
     * @return GpsTopic
     */
    protected function createTopic(string $name): InteropTopic
    {
        return new GpsTopic($name);
    }

    /**
     * @return GpsMessage
     */
    protected function createMessage(): InteropMessage
    {
        return new GpsMessage();
    }

    protected function getRouterTransportName(): string
    {
        return 'aprefix.router';
    }
}
