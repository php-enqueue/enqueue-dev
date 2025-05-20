<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\Null\NullQueue;
use Enqueue\RdKafka\JsonSerializer;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\Serializer;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RdKafkaContextTest extends TestCase
{
    public function testThrowNotImplementedOnCreateTemporaryQueue()
    {
        $context = new RdKafkaContext([]);

        $this->expectException(TemporaryQueueNotSupportedException::class);

        $context->createTemporaryQueue();
    }

    public function testThrowInvalidDestinationIfInvalidDestinationGivenOnCreateConsumer()
    {
        $context = new RdKafkaContext([]);

        $this->expectException(InvalidDestinationException::class);
        $context->createConsumer(new NullQueue('aQueue'));
    }

    public function testShouldSetJsonSerializerInConstructor()
    {
        $context = new RdKafkaContext([]);

        $this->assertInstanceOf(JsonSerializer::class, $context->getSerializer());
    }

    public function testShouldUseStringSerializerClassFromConfig()
    {
        $mockSerializerClass = get_class($this->createMock(Serializer::class));

        $context = new RdKafkaContext([
            'serializer' => $mockSerializerClass
        ]);

        $this->assertInstanceOf($mockSerializerClass, $context->getSerializer());
    }

    public function testShouldThrowExceptionOnInvalidSerializerConfig()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid serializer configuration');

        new RdKafkaContext([
            'serializer' => 123
        ]);
    }

    public function testShouldAllowGetPreviouslySetSerializer()
    {
        $context = new RdKafkaContext([]);

        $expectedSerializer = $this->createMock(Serializer::class);

        $context->setSerializer($expectedSerializer);

        $this->assertSame($expectedSerializer, $context->getSerializer());
    }

    public function testShouldInjectItsSerializerToProducer()
    {
        $context = new RdKafkaContext([]);

        $producer = $context->createProducer();

        $this->assertSame($context->getSerializer(), $producer->getSerializer());
    }

    public function testShouldInjectItsSerializerToConsumer()
    {
        $context = new RdKafkaContext(['global' => [
            'group.id' => uniqid('', true),
        ]]);

        $producer = $context->createConsumer($context->createQueue('aQueue'));

        $this->assertSame($context->getSerializer(), $producer->getSerializer());
    }

    public function testShouldNotCreateConsumerTwice()
    {
        $context = new RdKafkaContext(['global' => [
            'group.id' => uniqid('', true),
        ]]);
        $queue = $context->createQueue('aQueue');

        $consumer = $context->createConsumer($queue);
        $consumer2 = $context->createConsumer($queue);

        $this->assertSame($consumer, $consumer2);
    }

    public function testShouldCreateTwoConsumers()
    {
        $context = new RdKafkaContext(['global' => [
            'group.id' => uniqid('', true),
        ]]);
        $queueA = $context->createQueue('aQueue');
        $queueB = $context->createQueue('bQueue');

        $consumer = $context->createConsumer($queueA);
        $consumer2 = $context->createConsumer($queueB);

        $this->assertNotSame($consumer, $consumer2);
    }
}
