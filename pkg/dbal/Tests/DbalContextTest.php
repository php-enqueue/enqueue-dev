<?php

namespace Enqueue\Dbal\Tests;

use Doctrine\DBAL\Connection;
use Enqueue\Dbal\DbalConsumer;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Dbal\DbalProducer;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DbalContextTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(Context::class, DbalContext::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalContext($this->createConnectionMock());
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new DbalContext($this->createConnectionMock(), []);

        $this->assertAttributeEquals([
            'table_name' => 'enqueue',
            'polling_interval' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new DbalContext($this->createConnectionMock(), [
            'table_name' => 'theTableName',
            'polling_interval' => 12345,
        ]);

        $this->assertAttributeEquals([
            'table_name' => 'theTableName',
            'polling_interval' => 12345,
        ], 'config', $factory);
    }

    public function testShouldCreateMessage()
    {
        $context = new DbalContext($this->createConnectionMock());
        $message = $context->createMessage('body', ['pkey' => 'pval'], ['hkey' => 'hval']);

        $this->assertInstanceOf(DbalMessage::class, $message);
        $this->assertEquals('body', $message->getBody());
        $this->assertEquals(['pkey' => 'pval'], $message->getProperties());
        $this->assertEquals(['hkey' => 'hval'], $message->getHeaders());
        $this->assertNull($message->getPriority());
        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldConvertArrayToDbalMessage()
    {
        $arrayData = [
            'body' => 'theBody',
            'properties' => json_encode(['barProp' => 'barPropVal']),
            'headers' => json_encode(['fooHeader' => 'fooHeaderVal']),
        ];
        $context = new DbalContext($this->createConnectionMock());
        $message = $context->convertMessage($arrayData);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }

    public function testShouldCreateTopic()
    {
        $context = new DbalContext($this->createConnectionMock());
        $topic = $context->createTopic('topic');

        $this->assertInstanceOf(DbalDestination::class, $topic);
        $this->assertEquals('topic', $topic->getTopicName());
    }

    public function testShouldCreateQueue()
    {
        $context = new DbalContext($this->createConnectionMock());
        $queue = $context->createQueue('queue');

        $this->assertInstanceOf(DbalDestination::class, $queue);
        $this->assertEquals('queue', $queue->getQueueName());
    }

    public function testShouldCreateProducer()
    {
        $context = new DbalContext($this->createConnectionMock());

        $this->assertInstanceOf(DbalProducer::class, $context->createProducer());
    }

    public function testShouldCreateConsumer()
    {
        $context = new DbalContext($this->createConnectionMock());

        $this->assertInstanceOf(DbalConsumer::class, $context->createConsumer(new DbalDestination('')));
    }

    public function testShouldCreateMessageConsumerAndSetPollingInterval()
    {
        $context = new DbalContext($this->createConnectionMock(), [
            'polling_interval' => 123456,
        ]);

        $consumer = $context->createConsumer(new DbalDestination(''));

        $this->assertInstanceOf(DbalConsumer::class, $consumer);
        $this->assertEquals(123456, $consumer->getPollingInterval());
    }

    public function testShouldThrowIfDestinationIsInvalidInstanceType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage(
            'The destination must be an instance of '.
            'Enqueue\Dbal\DbalDestination but got '.
            'Enqueue\Dbal\Tests\NotSupportedDestination2.'
        );

        $context = new DbalContext($this->createConnectionMock());

        $this->assertInstanceOf(DbalConsumer::class, $context->createConsumer(new NotSupportedDestination2()));
    }

    public function testShouldReturnInstanceOfConnection()
    {
        $context = new DbalContext($connection = $this->createConnectionMock());

        $this->assertSame($connection, $context->getDbalConnection());
    }

    public function testShouldReturnConfig()
    {
        $context = new DbalContext($connection = $this->createConnectionMock());

        $this->assertSame($connection, $context->getDbalConnection());
    }

    public function testShouldThrowBadMethodCallExceptionOncreateTemporaryQueueCall()
    {
        $context = new DbalContext($connection = $this->createConnectionMock());

        $this->expectException(TemporaryQueueNotSupportedException::class);

        $context->createTemporaryQueue();
    }

    /**
     * @return MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }
}

class NotSupportedDestination2 implements Destination
{
}
