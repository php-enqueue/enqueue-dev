<?php

declare(strict_types=1);

namespace Enqueue\Dbal\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Enqueue\Dbal\DbalConsumer;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\DbalProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class DbalConsumerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, DbalConsumer::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalConsumer($this->createContextMock(), new DbalDestination('queue'));
    }

    public function testShouldReturnInstanceOfDestination()
    {
        $destination = new DbalDestination('queue');

        $consumer = new DbalConsumer($this->createContextMock(), $destination);

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testAcknowledgeShouldThrowIfInstanceOfMessageIsInvalid()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage(
            'The message must be an instance of '.
            'Enqueue\Dbal\DbalMessage '.
            'but it is Enqueue\Dbal\Tests\InvalidMessage.'
        );

        $consumer = new DbalConsumer($this->createContextMock(), new DbalDestination('queue'));
        $consumer->acknowledge(new InvalidMessage());
    }

    public function testShouldDeleteMessageOnAcknowledge()
    {
        $deliveryId = Uuid::uuid4();

        $queue = new DbalDestination('queue');

        $message = new DbalMessage();
        $message->setBody('theBody');
        $message->setDeliveryId($deliveryId->toString());

        $dbal = $this->createConectionMock();
        $dbal
            ->expects($this->once())
            ->method('delete')
            ->with(
                'some-table-name',
                ['delivery_id' => $deliveryId->toString()],
                ['delivery_id' => Type::GUID]
            )
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getDbalConnection')
            ->willReturn($dbal)
        ;
        $context
            ->expects($this->once())
            ->method('getTableName')
            ->willReturn('some-table-name')
        ;

        $consumer = new DbalConsumer($context, $queue);

        $consumer->acknowledge($message);
    }

    public function testCouldSetAndGetPollingInterval()
    {
        $destination = new DbalDestination('queue');

        $consumer = new DbalConsumer($this->createContextMock(), $destination);
        $consumer->setPollingInterval(123456);

        $this->assertEquals(123456, $consumer->getPollingInterval());
    }

    public function testCouldSetAndGetRedeliveryDelay()
    {
        $destination = new DbalDestination('queue');

        $consumer = new DbalConsumer($this->createContextMock(), $destination);
        $consumer->setRedeliveryDelay(123456);

        $this->assertEquals(123456, $consumer->getRedeliveryDelay());
    }

    public function testRejectShouldThrowIfInstanceOfMessageIsInvalid()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage(
            'The message must be an instance of '.
            'Enqueue\Dbal\DbalMessage '.
            'but it is Enqueue\Dbal\Tests\InvalidMessage.'
        );

        $consumer = new DbalConsumer($this->createContextMock(), new DbalDestination('queue'));
        $consumer->reject(new InvalidMessage());
    }

    public function testShouldDeleteMessageFromQueueOnReject()
    {
        $deliveryId = Uuid::uuid4();

        $queue = new DbalDestination('queue');

        $message = new DbalMessage();
        $message->setBody('theBody');
        $message->setDeliveryId($deliveryId->toString());

        $dbal = $this->createConectionMock();
        $dbal
            ->expects($this->once())
            ->method('delete')
            ->with(
                'some-table-name',
                ['delivery_id' => $deliveryId->toString()],
                ['delivery_id' => Type::GUID]
            )
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getDbalConnection')
            ->willReturn($dbal)
        ;
        $context
            ->expects($this->once())
            ->method('getTableName')
            ->willReturn('some-table-name')
        ;

        $consumer = new DbalConsumer($context, $queue);

        $consumer->reject($message);
    }

    public function testRejectShouldReSendMessageToSameQueueOnRequeue()
    {
        $queue = new DbalDestination('queue');

        $message = new DbalMessage();
        $message->setBody('theBody');
        $message->setDeliveryId(__METHOD__);

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->isInstanceOf(DbalMessage::class))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producerMock)
        ;

        $consumer = new DbalConsumer($context, $queue);

        $consumer->reject($message, true);
    }

    /**
     * @return DbalProducer|MockObject
     */
    private function createProducerMock()
    {
        return $this->createMock(DbalProducer::class);
    }

    /**
     * @return MockObject|DbalContext
     */
    private function createContextMock()
    {
        return $this->createMock(DbalContext::class);
    }

    /**
     * @return MockObject|DbalContext
     */
    private function createConectionMock()
    {
        return $this->createMock(Connection::class);
    }
}

class InvalidMessage implements Message
{
    public function getBody()
    {
        throw new \BadMethodCallException('This should not be called directly');
    }

    public function setBody($body): void
    {
    }

    public function setProperties(array $properties): void
    {
    }

    public function getProperties(): array
    {
        throw new \BadMethodCallException('This should not be called directly');
    }

    public function setProperty(string $name, $value): void
    {
    }

    public function getProperty(string $name, $default = null)
    {
    }

    public function setHeaders(array $headers): void
    {
    }

    public function getHeaders(): array
    {
        throw new \BadMethodCallException('This should not be called directly');
    }

    public function setHeader(string $name, $value): void
    {
    }

    public function getHeader(string $name, $default = null)
    {
    }

    public function setRedelivered(bool $redelivered): void
    {
    }

    public function isRedelivered(): bool
    {
        throw new \BadMethodCallException('This should not be called directly');
    }

    public function setCorrelationId(string $correlationId = null): void
    {
    }

    public function getCorrelationId(): ?string
    {
        throw new \BadMethodCallException('This should not be called directly');
    }

    public function setMessageId(string $messageId = null): void
    {
    }

    public function getMessageId(): ?string
    {
        throw new \BadMethodCallException('This should not be called directly');
    }

    public function getTimestamp(): ?int
    {
        throw new \BadMethodCallException('This should not be called directly');
    }

    public function setTimestamp(int $timestamp = null): void
    {
    }

    public function setReplyTo(string $replyTo = null): void
    {
    }

    public function getReplyTo(): ?string
    {
        throw new \BadMethodCallException('This should not be called directly');
    }
}
