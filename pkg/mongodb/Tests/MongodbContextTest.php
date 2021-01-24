<?php

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbConsumer;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Mongodb\MongodbDestination;
use Enqueue\Mongodb\MongodbMessage;
use Enqueue\Mongodb\MongodbProducer;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use MongoDB\Client;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 */
class MongodbContextTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(Context::class, MongodbContext::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new MongodbContext($this->createClientMock());
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $context = new MongodbContext($this->createClientMock(), []);

        $this->assertAttributeEquals([
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
            'polling_interval' => null,
        ], 'config', $context);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $client = new MongodbContext($this->createClientMock(), [
            'dbname' => 'testDbName',
            'collection_name' => 'testCollectionName',
            'polling_interval' => 123456,
        ]);

        $this->assertAttributeEquals([
            'dbname' => 'testDbName',
            'collection_name' => 'testCollectionName',
            'polling_interval' => 123456,
        ], 'config', $client);
    }

    public function testShouldCreateMessage()
    {
        $context = new MongodbContext($this->createClientMock());
        $message = $context->createMessage('body', ['pkey' => 'pval'], ['hkey' => 'hval']);

        $this->assertInstanceOf(MongodbMessage::class, $message);
        $this->assertEquals('body', $message->getBody());
        $this->assertEquals(['pkey' => 'pval'], $message->getProperties());
        $this->assertEquals(['hkey' => 'hval'], $message->getHeaders());
        $this->assertNull($message->getPriority());
        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldConvertFromArrayToMongodbMessage()
    {
        $arrayData = [
            '_id' => 'stringId',
            'body' => 'theBody',
            'properties' => json_encode(['barProp' => 'barPropVal']),
            'headers' => json_encode(['fooHeader' => 'fooHeaderVal']),
            'priority' => '12',
            'published_at' => 1525935820,
            'redelivered' => false,
        ];

        $context = new MongodbContext($this->createClientMock());
        $message = $context->convertMessage($arrayData);

        $this->assertInstanceOf(MongodbMessage::class, $message);

        $this->assertEquals('stringId', $message->getId());
        $this->assertEquals('theBody', $message->getBody());
        $this->assertEquals(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertEquals(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
        $this->assertEquals(12, $message->getPriority());
        $this->assertEquals(1525935820, $message->getPublishedAt());
        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldCreateTopic()
    {
        $context = new MongodbContext($this->createClientMock());
        $topic = $context->createTopic('topic');

        $this->assertInstanceOf(MongodbDestination::class, $topic);
        $this->assertEquals('topic', $topic->getTopicName());
    }

    public function testShouldCreateQueue()
    {
        $context = new MongodbContext($this->createClientMock());
        $queue = $context->createQueue('queue');

        $this->assertInstanceOf(MongodbDestination::class, $queue);
        $this->assertEquals('queue', $queue->getName());
    }

    public function testShouldCreateProducer()
    {
        $context = new MongodbContext($this->createClientMock());

        $this->assertInstanceOf(MongodbProducer::class, $context->createProducer());
    }

    public function testShouldCreateConsumer()
    {
        $context = new MongodbContext($this->createClientMock());

        $this->assertInstanceOf(MongodbConsumer::class, $context->createConsumer(new MongodbDestination('')));
    }

    public function testShouldCreateMessageConsumerAndSetPollingInterval()
    {
        $context = new MongodbContext($this->createClientMock(), [
            'polling_interval' => 123456,
        ]);

        $consumer = $context->createConsumer(new MongodbDestination(''));

        $this->assertInstanceOf(MongodbConsumer::class, $consumer);
        $this->assertEquals(123456, $consumer->getPollingInterval());
    }

    public function testShouldThrowIfDestinationIsInvalidInstanceType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage(
            'The destination must be an instance of '.
            'Enqueue\Mongodb\MongodbDestination but got '.
            'Enqueue\Mongodb\Tests\NotSupportedDestination2.'
        );

        $context = new MongodbContext($this->createClientMock());

        $this->assertInstanceOf(MongodbConsumer::class, $context->createConsumer(new NotSupportedDestination2()));
    }

    public function testShouldReturnInstanceOfClient()
    {
        $context = new MongodbContext($client = $this->createClientMock());

        $this->assertSame($client, $context->getClient());
    }

    public function testShouldReturnConfig()
    {
        $context = new MongodbContext($this->createClientMock());

        $this->assertSame([
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
            'polling_interval' => null,
        ], $context->getConfig());
    }

    public function testShouldThrowNotSupportedOnCreateTemporaryQueueCall()
    {
        $context = new MongodbContext($this->createClientMock());

        $this->expectException(TemporaryQueueNotSupportedException::class);

        $context->createTemporaryQueue();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Client
     */
    private function createClientMock()
    {
        return $this->createMock(Client::class);
    }
}

class NotSupportedDestination2 implements Destination
{
}
