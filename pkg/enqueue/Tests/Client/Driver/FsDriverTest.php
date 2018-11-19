<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Driver\FsDriver;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use Enqueue\Fs\FsProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use Interop\Queue\Topic as InteropTopic;
use Makasim\File\TempFile;
use PHPUnit\Framework\TestCase;

class FsDriverTest extends TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, FsDriver::class);
    }

    public function testShouldBeSubClassOfGenericDriver()
    {
        $this->assertClassExtends(GenericDriver::class, FsDriver::class);
    }

    public function testShouldSetupBroker()
    {
        $routerQueue = new FsDestination(TempFile::generate());

        $processorQueue = new FsDestination(TempFile::generate());

        $context = $this->createContextMock();
        // setup router
        $context
            ->expects($this->at(0))
            ->method('createQueue')
            ->willReturn($routerQueue)
        ;
        $context
            ->expects($this->at(1))
            ->method('declareDestination')
            ->with($this->identicalTo($routerQueue))
        ;
        // setup processor queue
        $context
            ->expects($this->at(2))
            ->method('createQueue')
            ->willReturn($processorQueue)
        ;
        $context
            ->expects($this->at(3))
            ->method('declareDestination')
            ->with($this->identicalTo($processorQueue))
        ;

        $routeCollection = new RouteCollection([
            new Route('aTopic', Route::TOPIC, 'aProcessor'),
        ]);

        $driver = new FsDriver(
            $context,
            $this->createDummyConfig(),
            $routeCollection
        );

        $driver->setupBroker();
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new FsDriver(...$args);
    }

    /**
     * @return FsContext
     */
    protected function createContextMock(): Context
    {
        return $this->createMock(FsContext::class);
    }

    /**
     * @return FsProducer
     */
    protected function createProducerMock(): InteropProducer
    {
        return $this->createMock(FsProducer::class);
    }

    /**
     * @return FsDestination
     */
    protected function createQueue(string $name): InteropQueue
    {
        return new FsDestination(new \SplFileInfo($name));
    }

    /**
     * @return FsDestination
     */
    protected function createTopic(string $name): InteropTopic
    {
        return new FsDestination(new \SplFileInfo($name));
    }

    /**
     * @return FsMessage
     */
    protected function createMessage(): InteropMessage
    {
        return new FsMessage();
    }
}
