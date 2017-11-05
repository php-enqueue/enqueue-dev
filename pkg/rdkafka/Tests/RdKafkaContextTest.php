<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\Null\NullQueue;
use Enqueue\RdKafka\JsonSerializer;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaTopic;
use Enqueue\RdKafka\Serializer;
use Interop\Queue\InvalidDestinationException;
use PHPUnit\Framework\TestCase;

/**
 * @group rdkafka
 */
class RdKafkaContextTest extends TestCase
{
    public function testThrowNotImplementedOnCreateTemporaryQueue()
    {
        $context = new RdKafkaContext([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not implemented');
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

    public function testTopicCreatedByContextShouldContainDefaultTopicConf()
    {
        $context = new RdKafkaContext(
            [
                'topic' => [
                    'request.required.acks' => '-1', // all ISRs must ack the message
                ],
            ]
        );

        $rdKafkaTopic = $context->createTopic('test-topic');
        $configDump = $rdKafkaTopic->getConf()->dump();

        $this->assertInstanceOf(RdKafkaTopic::class, $rdKafkaTopic);
        $this->assertSame('-1', $configDump['request.required.acks']);
    }
}
