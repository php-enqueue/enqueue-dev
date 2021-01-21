<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\Driver\RdKafkaDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\RdKafkaProducer;
use Enqueue\RdKafka\RdKafkaTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use PHPUnit\Framework\TestCase;

/**
 * @group rdkafka
 */
class RdKafkaDriverTest extends TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, RdKafkaDriver::class);
    }

    public function testShouldBeSubClassOfGenericDriver()
    {
        $this->assertClassExtends(GenericDriver::class, RdKafkaDriver::class);
    }

    public function testShouldSetupBroker()
    {
        $routerTopic = new RdKafkaTopic('');
        $routerQueue = new RdKafkaTopic('');

        $processorTopic = new RdKafkaTopic('');

        $context = $this->createContextMock();

        $context
            ->expects(self::once())
            ->method('createQueue')
            ->willReturn($routerTopic)
        ;
        $context
            ->expects(self::once())
            ->method('createQueue')
            ->willReturn($routerQueue)
        ;
        $context
            ->expects(self::once())
            ->method('createQueue')
            ->willReturn($processorTopic)
        ;

        $driver = new RdKafkaDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([
                new Route('topic', Route::TOPIC, 'processor'),
            ])
        );

        $driver->setupBroker();
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new RdKafkaDriver(...$args);
    }

    /**
     * @return RdKafkaContext
     */
    protected function createContextMock(): Context
    {
        return $this->createMock(RdKafkaContext::class);
    }

    /**
     * @return RdKafkaProducer
     */
    protected function createProducerMock(): InteropProducer
    {
        return $this->createMock(RdKafkaProducer::class);
    }

    /**
     * @return RdKafkaTopic
     */
    protected function createQueue(string $name): InteropQueue
    {
        return new RdKafkaTopic($name);
    }

    /**
     * @return RdKafkaTopic
     */
    protected function createTopic(string $name): RdKafkaTopic
    {
        return new RdKafkaTopic($name);
    }

    /**
     * @return RdKafkaMessage
     */
    protected function createMessage(): InteropMessage
    {
        return new RdKafkaMessage();
    }

    /**
     * @return Config
     */
    private function createDummyConfig()
    {
        return Config::create('aPrefix');
    }
}
