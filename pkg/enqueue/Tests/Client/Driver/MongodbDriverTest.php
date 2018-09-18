<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\Driver\MongodbDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\RouteCollection;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbDestination;
use Enqueue\Mongodb\MongodbMessage;
use Enqueue\Mongodb\MongodbProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

class MongodbDriverTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, MongodbDriver::class);
    }

    public function testShouldBeSubClassOfGenericDriver()
    {
        $this->assertClassExtends(GenericDriver::class, MongodbDriver::class);
    }

    public function testShouldSetupBroker()
    {
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createCollection')
        ;
        $context
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn([
                'dbname' => 'aDb',
                'collection_name' => 'aCol',
            ])
        ;

        $driver = new MongodbDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        $driver->setupBroker();
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new MongodbDriver(...$args);
    }

    /**
     * @return MongodbContext
     */
    protected function createContextMock(): PsrContext
    {
        return $this->createMock(MongodbContext::class);
    }

    /**
     * @return MongodbProducer
     */
    protected function createProducerMock(): PsrProducer
    {
        return $this->createMock(MongodbProducer::class);
    }

    /**
     * @return MongodbDestination
     */
    protected function createQueue(string $name): PsrQueue
    {
        return new MongodbDestination($name);
    }

    /**
     * @return MongodbDestination
     */
    protected function createTopic(string $name): PsrTopic
    {
        return new MongodbDestination($name);
    }

    /**
     * @return MongodbMessage
     */
    protected function createMessage(): PsrMessage
    {
        return new MongodbMessage();
    }
}
