<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\NoEffect\NullMessage;
use Enqueue\NoEffect\NullQueue;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\RdKafkaProducer;
use Enqueue\RdKafka\RdKafkaTopic;
use Enqueue\RdKafka\Serializer;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use PHPUnit\Framework\TestCase;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;

/**
 * @group rdkafka
 */
class RdKafkaProducerTest extends TestCase
{
    public function testCouldBeConstructedWithKafkaProducerAndSerializerAsArguments()
    {
        new RdKafkaProducer($this->createKafkaProducerMock(), $this->createSerializerMock());
    }

    public function testThrowIfDestinationInvalid()
    {
        $producer = new RdKafkaProducer($this->createKafkaProducerMock(), $this->createSerializerMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\RdKafka\RdKafkaTopic but got Enqueue\NoEffect\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new RdKafkaMessage());
    }

    public function testThrowIfMessageInvalid()
    {
        $producer = new RdKafkaProducer($this->createKafkaProducerMock(), $this->createSerializerMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\RdKafka\RdKafkaMessage but it is Enqueue\NoEffect\NullMessage.');
        $producer->send(new RdKafkaTopic('aQueue'), new NullMessage());
    }

    public function testShouldUseSerializerToEncodeMessageAndPutToExpectedTube()
    {
        $messageHeaders = ['bar' => 'barVal'];
        $message = new RdKafkaMessage('theBody', ['foo' => 'fooVal'], $messageHeaders);
        $message->setKey('key');

        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('producev')
            ->with(
                RD_KAFKA_PARTITION_UA,
                0,
                'theSerializedMessage',
                'key',
                $messageHeaders
            )
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->with('theQueueName')
            ->willReturn($kafkaTopic)
        ;
        $kafkaProducer
            ->expects($this->once())
            ->method('poll')
            ->with(0)
        ;

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('toString')
            ->with($this->identicalTo($message))
            ->willReturn('theSerializedMessage')
        ;

        $producer = new RdKafkaProducer($kafkaProducer, $serializer);

        $producer->send(new RdKafkaTopic('theQueueName'), $message);
    }

    public function testShouldPassNullAsTopicConfigIfNotSetOnTopic()
    {
        // guard
        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('producev')
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->with('theQueueName', null)
            ->willReturn($kafkaTopic)
        ;
        $kafkaProducer
            ->expects($this->once())
            ->method('poll')
            ->with(0)
        ;

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('toString')
            ->willReturn('aSerializedMessage')
        ;

        $producer = new RdKafkaProducer($kafkaProducer, $serializer);

        $topic = new RdKafkaTopic('theQueueName');

        // guard
        $this->assertNull($topic->getConf());

        $producer->send($topic, new RdKafkaMessage());
    }

    public function testShouldPassCustomConfAsTopicConfigIfSetOnTopic()
    {
        $conf = new TopicConf();

        // guard
        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('producev')
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->with('theQueueName', $this->identicalTo($conf))
            ->willReturn($kafkaTopic)
        ;
        $kafkaProducer
            ->expects($this->once())
            ->method('poll')
            ->with(0)
        ;

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('toString')
            ->willReturn('aSerializedMessage')
        ;

        $producer = new RdKafkaProducer($kafkaProducer, $serializer);

        $topic = new RdKafkaTopic('theQueueName');
        $topic->setConf($conf);

        $producer->send($topic, new RdKafkaMessage());
    }

    public function testShouldAllowGetPreviouslySetSerializer()
    {
        $producer = new RdKafkaProducer($this->createKafkaProducerMock(), $this->createSerializerMock());

        $expectedSerializer = $this->createSerializerMock();

        // guard
        $this->assertNotSame($producer->getSerializer(), $expectedSerializer);

        $producer->setSerializer($expectedSerializer);

        $this->assertSame($expectedSerializer, $producer->getSerializer());
    }

    public function testShouldAllowSerializersToSerializeKeys()
    {
        $messageHeaders = ['bar' => 'barVal'];
        $message = new RdKafkaMessage('theBody', ['foo' => 'fooVal'], $messageHeaders);
        $message->setKey('key');

        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('producev')
            ->with(
                RD_KAFKA_PARTITION_UA,
                0,
                'theSerializedMessage',
                'theSerializedKey'
            )
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->willReturn($kafkaTopic)
        ;
        $kafkaProducer
            ->expects($this->once())
            ->method('poll')
            ->with(0)
        ;

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('toString')
            ->willReturnCallback(function () use ($message) {
                $message->setKey('theSerializedKey');

                return 'theSerializedMessage';
            })
        ;

        $producer = new RdKafkaProducer($kafkaProducer, $serializer);
        $producer->send(new RdKafkaTopic('theQueueName'), $message);
    }

    public function testShouldGetPartitionFromMessage(): void
    {
        $partition = 1;

        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('producev')
            ->with(
                $partition,
                0,
                'theSerializedMessage',
                'theSerializedKey'
            )
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->willReturn($kafkaTopic)
        ;
        $kafkaProducer
            ->expects($this->once())
            ->method('poll')
            ->with(0)
        ;
        $messageHeaders = ['bar' => 'barVal'];
        $message = new RdKafkaMessage('theBody', ['foo' => 'fooVal'], $messageHeaders);
        $message->setKey('key');
        $message->setPartition($partition);

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('toString')
            ->willReturnCallback(function () use ($message) {
                $message->setKey('theSerializedKey');

                return 'theSerializedMessage';
            })
        ;

        $destination = new RdKafkaTopic('theQueueName');

        $producer = new RdKafkaProducer($kafkaProducer, $serializer);
        $producer->send($destination, $message);
    }

    public function testShouldGetPartitionFromDestination(): void
    {
        $partition = 2;

        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('producev')
            ->with(
                $partition,
                0,
                'theSerializedMessage',
                'theSerializedKey'
            )
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->willReturn($kafkaTopic)
        ;
        $kafkaProducer
            ->expects($this->once())
            ->method('poll')
            ->with(0)
        ;
        $messageHeaders = ['bar' => 'barVal'];
        $message = new RdKafkaMessage('theBody', ['foo' => 'fooVal'], $messageHeaders);
        $message->setKey('key');

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('toString')
            ->willReturnCallback(function () use ($message) {
                $message->setKey('theSerializedKey');

                return 'theSerializedMessage';
            })
        ;

        $destination = new RdKafkaTopic('theQueueName');
        $destination->setPartition($partition);

        $producer = new RdKafkaProducer($kafkaProducer, $serializer);
        $producer->send($destination, $message);
    }

    public function testShouldAllowFalsyKeyFromMessage(): void
    {
        $key = 0;

        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('producev')
            ->with(
                RD_KAFKA_PARTITION_UA,
                0,
                '',
                $key
            )
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->willReturn($kafkaTopic)
        ;

        $message = new RdKafkaMessage();
        $message->setKey($key);

        $producer = new RdKafkaProducer($kafkaProducer, $this->createSerializerMock());
        $producer->send(new RdKafkaTopic(''), $message);
    }

    public function testShouldAllowFalsyKeyFromDestination(): void
    {
        $key = 0;

        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('producev')
            ->with(
                RD_KAFKA_PARTITION_UA,
                0,
                '',
                $key
            )
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->willReturn($kafkaTopic)
        ;

        $destination = new RdKafkaTopic('');
        $destination->setKey($key);

        $producer = new RdKafkaProducer($kafkaProducer, $this->createSerializerMock());
        $producer->send($destination, new RdKafkaMessage());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ProducerTopic
     */
    private function createKafkaTopicMock()
    {
        return $this->createMock(ProducerTopic::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Producer
     */
    private function createKafkaProducerMock()
    {
        return $this->createMock(Producer::class);
    }

    /**
     * @return Serializer|\PHPUnit\Framework\MockObject\MockObject|Serializer
     */
    private function createSerializerMock()
    {
        return $this->createMock(Serializer::class);
    }
}
