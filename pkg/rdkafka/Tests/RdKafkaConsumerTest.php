<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\RdKafkaConsumer;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\RdKafkaTopic;
use Enqueue\RdKafka\Serializer;
use PHPUnit\Framework\TestCase;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

/**
 * @group rdkafka
 */
class RdKafkaConsumerTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new RdKafkaConsumer(
            $this->createKafkaConsumerMock(),
            $this->createContextMock(),
            new RdKafkaTopic(''),
            $this->createSerializerMock()
        );
    }

    public function testShouldReturnQueueSetInConstructor()
    {
        $destination = new RdKafkaTopic('');

        $consumer = new RdKafkaConsumer(
            $this->createKafkaConsumerMock(),
            $this->createContextMock(),
            $destination,
            $this->createSerializerMock()
        );

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testShouldReceiveFromQueueAndReturnNullIfNoMessageInQueue()
    {
        $destination = new RdKafkaTopic('dest');

        $kafkaMessage = new Message();
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        $kafkaConsumer = $this->createKafkaConsumerMock();
        $kafkaConsumer
            ->expects($this->once())
            ->method('assign')
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('consume')
            ->with(1000)
            ->willReturn($kafkaMessage)
        ;

        $consumer = new RdKafkaConsumer(
            $kafkaConsumer,
            $this->createContextMock(),
            $destination,
            $this->createSerializerMock()
        );

        $this->assertNull($consumer->receive(1000));
    }

    public function testShouldPassProperlyConfiguredTopicPartitionOnAssign()
    {
        $destination = new RdKafkaTopic('dest');

        $kafkaMessage = new Message();
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        $kafkaConsumer = $this->createKafkaConsumerMock();
        $kafkaConsumer
            ->expects($this->once())
            ->method('assign')
        ;
        $kafkaConsumer
            ->expects($this->any())
            ->method('consume')
            ->willReturn($kafkaMessage)
        ;

        $consumer = new RdKafkaConsumer(
            $kafkaConsumer,
            $this->createContextMock(),
            $destination,
            $this->createSerializerMock()
        );

        $consumer->receive(1000);
        $consumer->receive(1000);
        $consumer->receive(1000);
    }

    public function testShouldSubscribeOnFirstReceiveOnly()
    {
        $destination = new RdKafkaTopic('dest');

        $kafkaMessage = new Message();
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        $kafkaConsumer = $this->createKafkaConsumerMock();
        $kafkaConsumer
            ->expects($this->once())
            ->method('assign')
        ;
        $kafkaConsumer
            ->expects($this->any())
            ->method('consume')
            ->willReturn($kafkaMessage)
        ;

        $consumer = new RdKafkaConsumer(
            $kafkaConsumer,
            $this->createContextMock(),
            $destination,
            $this->createSerializerMock()
        );

        $consumer->receive(1000);
        $consumer->receive(1000);
        $consumer->receive(1000);
    }

    public function testThrowOnOffsetChangeAfterSubscribing()
    {
        $destination = new RdKafkaTopic('dest');

        $kafkaMessage = new Message();
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        $kafkaConsumer = $this->createKafkaConsumerMock();
        $kafkaConsumer
            ->expects($this->once())
            ->method('assign')
        ;
        $kafkaConsumer
            ->expects($this->any())
            ->method('consume')
            ->willReturn($kafkaMessage)
        ;

        $consumer = new RdKafkaConsumer(
            $kafkaConsumer,
            $this->createContextMock(),
            $destination,
            $this->createSerializerMock()
        );

        $consumer->receive(1000);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The consumer has already subscribed.');
        $consumer->setOffset(123);
    }

    public function testShouldReceiveFromQueueAndReturnMessageIfMessageInQueue()
    {
        $destination = new RdKafkaTopic('dest');

        $expectedMessage = new  RdKafkaMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $kafkaMessage = new Message();
        $kafkaMessage->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $kafkaMessage->payload = 'theSerializedMessage';

        $kafkaConsumer = $this->createKafkaConsumerMock();
        $kafkaConsumer
            ->expects($this->once())
            ->method('assign')
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('consume')
            ->with(1000)
            ->willReturn($kafkaMessage)
        ;

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('toMessage')
            ->with('theSerializedMessage')
            ->willReturn($expectedMessage)
        ;

        $consumer = new RdKafkaConsumer(
            $kafkaConsumer,
            $this->createContextMock(),
            $destination,
            $serializer
        );

        $actualMessage = $consumer->receive(1000);

        $this->assertSame($actualMessage, $expectedMessage);
        $this->assertSame($kafkaMessage, $actualMessage->getKafkaMessage());
    }

    public function testShouldThrowExceptionNotImplementedOnReceiveNoWait()
    {
        $consumer = new RdKafkaConsumer(
            $this->createKafkaConsumerMock(),
            $this->createContextMock(),
            new RdKafkaTopic(''),
            $this->createSerializerMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not implemented');

        $consumer->receiveNoWait();
    }

    public function testShouldAllowGetPreviouslySetSerializer()
    {
        $consumer = new RdKafkaConsumer(
            $this->createKafkaConsumerMock(),
            $this->createContextMock(),
            new RdKafkaTopic(''),
            $this->createSerializerMock()
        );

        $expectedSerializer = $this->createSerializerMock();

        //guard
        $this->assertNotSame($consumer->getSerializer(), $expectedSerializer);

        $consumer->setSerializer($expectedSerializer);

        $this->assertSame($expectedSerializer, $consumer->getSerializer());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|KafkaConsumer
     */
    private function createKafkaConsumerMock()
    {
        return $this->createMock(KafkaConsumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RdKafkaContext
     */
    private function createContextMock()
    {
        return $this->createMock(RdKafkaContext::class);
    }

    /**
     * @return Serializer|\PHPUnit_Framework_MockObject_MockObject|Serializer
     */
    private function createSerializerMock()
    {
        return $this->createMock(Serializer::class);
    }
}
