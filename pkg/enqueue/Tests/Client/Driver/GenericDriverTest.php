<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\MessagePriority;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;
use PHPUnit\Framework\TestCase;

class GenericDriverTest extends TestCase
{
    use ClassExtensionTrait;
    use GenericDriverTestsTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, GenericDriver::class);
    }

    protected function createDriver(...$args): DriverInterface
    {
        return new GenericDriver(...$args);
    }

    protected function createContextMock(): PsrContext
    {
        return $this->createMock(PsrContext::class);
    }

    protected function createProducerMock(): PsrProducer
    {
        return $this->createMock(PsrProducer::class);
    }

    protected function createQueue(string $name): PsrQueue
    {
        return new NullQueue($name);
    }

    protected function createTopic(string $name): PsrTopic
    {
        return new NullTopic($name);
    }

    protected function createMessage(): PsrMessage
    {
        return new NullMessage();
    }

    protected function assertTransportMessage(PsrMessage $transportMessage): void
    {
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertArraySubset([
            'hkey' => 'hval',
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
