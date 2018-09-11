<?php

namespace Enqueue\Tests;

use Enqueue\AmqpBunny\AmqpConnectionFactory as AmqpBunnyConnectionFactory;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpExtConnectionFactory;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnectionFactory;
use Enqueue\ConnectionFactoryFactory;
use Enqueue\ConnectionFactoryFactoryInterface;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Resources;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Stomp\StompConnectionFactory;
use PHPUnit\Framework\TestCase;

class ConnectionFactoryFactoryTest extends TestCase
{
    public function testShouldImplementConnectionFactoryFactoryInterface()
    {
        $rc = new \ReflectionClass(ConnectionFactoryFactory::class);

        $this->assertTrue($rc->implementsInterface(ConnectionFactoryFactoryInterface::class));
    }

    public function testShouldBeFinal()
    {
        $rc = new \ReflectionClass(ConnectionFactoryFactory::class);

        $this->assertTrue($rc->isFinal());
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new ConnectionFactoryFactory();
    }

    public function testShouldAcceptStringDSN()
    {
        $factory = new ConnectionFactoryFactory();

        $factory->create('null:');
    }

    public function testShouldAcceptArrayWithDsnKey()
    {
        $factory = new ConnectionFactoryFactory();

        $factory->create(['dsn' => 'null:']);
    }

    public function testThrowIfInvalidConfigGiven()
    {
        $factory = new ConnectionFactoryFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config must be either array or DSN string.');
        $factory->create(new \stdClass());
    }

    public function testThrowIfArrayConfigMissDsnKeyInvalidConfigGiven()
    {
        $factory = new ConnectionFactoryFactory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config must be either array or DSN string.');
        $factory->create(new \stdClass());
    }

    public function testThrowIfPackageThatSupportSchemeNotInstalled()
    {
        $scheme = 'scheme5b7aa7d7cd213';
        $class = 'ConnectionClass5b7aa7d7cd213';

        Resources::addConnection($class, [$scheme], [], 'thePackage');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To use given scheme "scheme5b7aa7d7cd213" a package has to be installed. Run "composer req thePackage" to add it.');
        (new ConnectionFactoryFactory())->create($scheme.'://foo');
    }

    public function testThrowIfSchemeIsNotKnown()
    {
        $scheme = 'scheme5b7aa862e70a5';

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A given scheme "scheme5b7aa862e70a5" is not supported. Maybe it is a custom connection, make sure you registered it with "Enqueue\Resources::addConnection".');
        (new ConnectionFactoryFactory())->create($scheme.'://foo');
    }

    public function testThrowIfDsnInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid. It does not have scheme separator ":".');

        (new ConnectionFactoryFactory())->create('invalid-scheme');
    }

    /**
     * @dataProvider provideDSN
     */
    public function testReturnsExpectedFactories(string $dsn, string $expectedFactoryClass)
    {
        $connectionFactory = (new ConnectionFactoryFactory())->create($dsn);

        $this->assertInstanceOf($expectedFactoryClass, $connectionFactory);
    }

    public static function provideDSN()
    {
        yield ['null:', NullConnectionFactory::class];

        yield ['amqp:', AmqpBunnyConnectionFactory::class];

        yield ['amqp+ext:', AmqpExtConnectionFactory::class];

//        yield ['amqp+rabbitmq:', AmqpExtConnectionFactory::class];

//        yield ['amqp+rabbitmq+ext:', AmqpExtConnectionFactory::class];

        yield ['amqps:', AmqpExtConnectionFactory::class];

        yield ['amqps+ext:', AmqpExtConnectionFactory::class];

//        yield ['amqps+rabbitmq:', AmqpExtConnectionFactory::class];

//        yield ['amqps+ext+rabbitmq:', AmqpExtConnectionFactory::class];

        yield ['amqp+bunny:', AmqpBunnyConnectionFactory::class];

        yield ['amqp+lib:', AmqpLibConnectionFactory::class];

        yield ['mssql:', DbalConnectionFactory::class];

        yield ['mysql:', DbalConnectionFactory::class];

        yield ['pgsql:', DbalConnectionFactory::class];

        yield ['file:', FsConnectionFactory::class];

        // https://github.com/php-enqueue/enqueue-dev/issues/511
//        yield ['gearman:', GearmanConnectionFactory::class];

        yield ['gps:', GpsConnectionFactory::class];

        yield ['mongodb:', MongodbConnectionFactory::class];

        yield ['beanstalk:', PheanstalkConnectionFactory::class];

        yield ['kafka:', RdKafkaConnectionFactory::class];

        yield ['redis:', RedisConnectionFactory::class];

        yield ['sqs:', SqsConnectionFactory::class];

        yield ['stomp:', StompConnectionFactory::class];
    }
}
