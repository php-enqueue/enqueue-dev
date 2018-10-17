<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Driver\DbalDriver;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\RouteCollection;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\DbalProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use Interop\Queue\Topic as InteropTopic;
use PHPUnit\Framework\TestCase;

class DbalDriverTest extends TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, DbalDriver::class);
    }

    public function testShouldBeSubClassOfGenericDriver()
    {
        $this->assertClassExtends(GenericDriver::class, DbalDriver::class);
    }

    public function testShouldSetupBroker()
    {
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getTableName')
        ;
        $context
            ->expects($this->once())
            ->method('createDataBaseTable')
        ;

        $driver = new DbalDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $driver->setupBroker();
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new DbalDriver(...$args);
    }

    /**
     * @return DbalContext
     */
    protected function createContextMock(): Context
    {
        return $this->createMock(DbalContext::class);
    }

    /**
     * @return DbalProducer
     */
    protected function createProducerMock(): InteropProducer
    {
        return $this->createMock(DbalProducer::class);
    }

    /**
     * @return DbalDestination
     */
    protected function createQueue(string $name): InteropQueue
    {
        return new DbalDestination($name);
    }

    /**
     * @return DbalDestination
     */
    protected function createTopic(string $name): InteropTopic
    {
        return new DbalDestination($name);
    }

    /**
     * @return DbalMessage
     */
    protected function createMessage(): InteropMessage
    {
        return new DbalMessage();
    }
}
