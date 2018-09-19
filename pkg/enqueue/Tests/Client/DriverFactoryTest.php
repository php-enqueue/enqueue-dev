<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\Driver\AmqpDriver;
use Enqueue\Client\Driver\DbalDriver;
use Enqueue\Client\Driver\FsDriver;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\Driver\GpsDriver;
use Enqueue\Client\Driver\MongodbDriver;
use Enqueue\Client\Driver\RabbitMqDriver;
use Enqueue\Client\Driver\RabbitMqStompDriver;
use Enqueue\Client\Driver\RdKafkaDriver;
use Enqueue\Client\Driver\SqsDriver;
use Enqueue\Client\Driver\StompDriver;
use Enqueue\Client\DriverFactory;
use Enqueue\Client\DriverFactoryInterface;
use Enqueue\Client\Resources;
use Enqueue\Client\RouteCollection;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\GpsContext;
use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Null\NullContext;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\RedisContext;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Queue\PsrConnectionFactory;
use PHPUnit\Framework\TestCase;

class DriverFactoryTest extends TestCase
{
    public function testShouldImplementDriverFactoryInterface()
    {
        $rc = new \ReflectionClass(DriverFactory::class);

        $this->assertTrue($rc->implementsInterface(DriverFactoryInterface::class));
    }

    public function testShouldBeFinal()
    {
        $rc = new \ReflectionClass(DriverFactory::class);

        $this->assertTrue($rc->isFinal());
    }

    public function testCouldBeConstructedWithConfigAndRouteCollectionAsArguments()
    {
        new DriverFactory($this->createConfigMock(), new RouteCollection([]));
    }

    public function testThrowIfPackageThatSupportSchemeNotInstalled()
    {
        $scheme = 'scheme5b7aa7d7cd213';
        $class = 'ConnectionClass5b7aa7d7cd213';

        Resources::addDriver($class, [$scheme], [], ['thePackage', 'theOtherPackage']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To use given scheme "scheme5b7aa7d7cd213" a package has to be installed. Run "composer req thePackage theOtherPackage" to add it.');
        $factory = new DriverFactory($this->createConfigMock(), new RouteCollection([]));

        $factory->create($this->createConnectionFactoryMock(), $scheme.'://foo', []);
    }

    public function testThrowIfSchemeIsNotKnown()
    {
        $scheme = 'scheme5b7aa862e70a5';

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A given scheme "scheme5b7aa862e70a5" is not supported. Maybe it is a custom driver, make sure you registered it with "Enqueue\Client\Resources::addDriver".');

        $factory = new DriverFactory($this->createConfigMock(), new RouteCollection([]));

        $factory->create($this->createConnectionFactoryMock(), $scheme.'://foo', []);
    }

    public function testThrowIfDsnInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid. It does not have scheme separator ":".');

        $factory = new DriverFactory($this->createConfigMock(), new RouteCollection([]));

        $factory->create($this->createConnectionFactoryMock(), 'invalidDsn', []);
    }

    /**
     * @dataProvider provideDSN
     */
    public function testReturnsExpectedFactories(
        string $dsn,
        string $connectionFactoryClass,
        string $contextClass,
        array $conifg,
        string $expectedDriverClass
    ) {
        $connectionFactoryMock = $this->createMock($connectionFactoryClass);
        $connectionFactoryMock
            ->expects($this->once())
            ->method('createContext')
            ->willReturn($this->createMock($contextClass))
        ;

        $driverFactory = new DriverFactory($this->createConfigMock(), new RouteCollection([]));

        $driver = $driverFactory->create($connectionFactoryMock, $dsn, $conifg);

        $this->assertInstanceOf($expectedDriverClass, $driver);
    }

    public static function provideDSN()
    {
        yield ['null:', NullConnectionFactory::class, NullContext::class, [], GenericDriver::class];

        yield ['amqp:', AmqpConnectionFactory::class, AmqpContext::class, [], AmqpDriver::class];

        yield ['amqp+rabbitmq:', AmqpConnectionFactory::class, AmqpContext::class, [], RabbitMqDriver::class];

        yield ['mysql:', DbalConnectionFactory::class, DbalContext::class, [], DbalDriver::class];

        yield ['file:', FsConnectionFactory::class, FsContext::class, [], FsDriver::class];

        // https://github.com/php-enqueue/enqueue-dev/issues/511
//        yield ['gearman:', GearmanConnectionFactory::class, NullContext::class, [], NullDriver::class];

        yield ['gps:', GpsConnectionFactory::class, GpsContext::class, [], GpsDriver::class];

        yield ['mongodb:', MongodbConnectionFactory::class, MongodbContext::class, [], MongodbDriver::class];

        yield ['kafka:', RdKafkaConnectionFactory::class, RdKafkaContext::class, [], RdKafkaDriver::class];

        yield ['redis:', RedisConnectionFactory::class, RedisContext::class, [], GenericDriver::class];

        yield ['redis+predis:', RedisConnectionFactory::class, RedisContext::class, [], GenericDriver::class];

        yield ['sqs:', SqsConnectionFactory::class, SqsContext::class, [], SqsDriver::class];

        yield ['stomp:', StompConnectionFactory::class, StompContext::class, [], StompDriver::class];

        yield ['stomp+rabbitmq:', StompConnectionFactory::class, StompContext::class, [], RabbitMqStompDriver::class];

        yield ['stomp+foo+bar:', StompConnectionFactory::class, StompContext::class, [], StompDriver::class];
    }

    private function createConnectionFactoryMock(): PsrConnectionFactory
    {
        return $this->createMock(PsrConnectionFactory::class);
    }

    private function createConfigMock(): Config
    {
        return $this->createMock(Config::class);
    }
}
