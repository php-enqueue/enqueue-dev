<?php

namespace Enqueue\Tests\Client\Driver;

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
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
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
    protected function createContextMock(): PsrContext
    {
        return $this->createMock(AmqpContext::class);
    }

    /**
     * @return AmqpProducer
     */
    protected function createProducerMock(): PsrProducer
    {
        return $this->createMock(AmqpProducer::class);
    }

    /**
     * @return AmqpQueue
     */
    protected function createQueue(string $name): PsrQueue
    {
        return new AmqpQueue($name);
    }

    /**
     * @return AmqpTopic
     */
    protected function createTopic(string $name): AmqpTopic
    {
        return new AmqpTopic($name);
    }

    /**
     * @return AmqpMessage
     */
    protected function createMessage(): PsrMessage
    {
        return new AmqpMessage();
    }

    protected function assertTransportMessage(PsrMessage $transportMessage): void
    {
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertArraySubset([
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
            'X-Enqueue-Content-Type' => 'ContentType',
            'X-Enqueue-Priority' => MessagePriority::HIGH,
            'X-Enqueue-Expire' => 123,
            'X-Enqueue-Delay' => 345,
        ], $transportMessage->getProperties());
        $this->assertSame('theMessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }
}
