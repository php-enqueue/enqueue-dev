<?php

namespace Enqueue\Tests\Client\Driver;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Enqueue\Client\Config;
use Enqueue\Client\Driver\AmqpDriver;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\Driver\RabbitMqDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\RouteCollection;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpProducer;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use PHPUnit\Framework\TestCase;

class RabbitMqDriverTest extends TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, RabbitMqDriver::class);
    }

    public function testShouldBeSubClassOfGenericDriver()
    {
        $this->assertClassExtends(GenericDriver::class, RabbitMqDriver::class);
    }

    public function testShouldBeSubClassOfAmqpDriver()
    {
        $this->assertClassExtends(AmqpDriver::class, RabbitMqDriver::class);
    }

    public function testShouldCreateQueueWithMaxPriorityArgument()
    {
        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($this->createQueue('aName'))
        ;

        $driver = $this->createDriver(
            $context,
            $this->createDummyConfig(),
            new RouteCollection([])
        );

        /** @var AmqpQueue $queue */
        $queue = $driver->createQueue('aName');

        $this->assertSame(['x-max-priority' => 4], $queue->getArguments());
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new RabbitMqDriver(...$args);
    }

    /**
     * @return AmqpContext
     */
    protected function createContextMock(): Context
    {
        return $this->createMock(AmqpContext::class);
    }

    /**
     * @return AmqpProducer
     */
    protected function createProducerMock(): InteropProducer
    {
        return $this->createMock(AmqpProducer::class);
    }

    /**
     * @return AmqpQueue
     */
    protected function createQueue(string $name): InteropQueue
    {
        return new AmqpQueue($name);
    }

    protected function createTopic(string $name): AmqpTopic
    {
        return new AmqpTopic($name);
    }

    /**
     * @return AmqpMessage
     */
    protected function createMessage(): InteropMessage
    {
        return new AmqpMessage();
    }

    protected function getRouterTransportName(): string
    {
        return 'aprefix.router';
    }

    protected function assertTransportMessage(InteropMessage $transportMessage): void
    {
        $this->assertSame('body', $transportMessage->getBody());
        Assert::assertArraySubset([
            'hkey' => 'hval',
            'delivery_mode' => AmqpMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => 'ContentType',
            'expiration' => '123000',
            'priority' => 3,
            'message_id' => 'theMessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $transportMessage->getHeaders());
        $this->assertEquals([
            'pkey' => 'pval',
            Config::CONTENT_TYPE => 'ContentType',
            Config::PRIORITY => MessagePriority::HIGH,
            Config::EXPIRE => 123,
            Config::DELAY => 345,
        ], $transportMessage->getProperties());
        $this->assertSame('theMessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }
}
