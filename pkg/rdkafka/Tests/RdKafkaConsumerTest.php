<?php
namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\RdKafkaConsumer;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\RdKafkaTopic;
use PHPUnit\Framework\TestCase;
use RdKafka\KafkaConsumer;
use RdKafka\Message;

class RdKafkaConsumerTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new RdKafkaConsumer($this->createKafkaConsumerMock(), $this->createContextMock(), new RdKafkaTopic(''));
    }

    public function testShouldReturnQueueSetInConstructor()
    {
        $destination = new RdKafkaTopic('');

        $consumer = new RdKafkaConsumer($this->createKafkaConsumerMock(), $this->createContextMock(), $destination);

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
            ->method('subscribe')
            ->with(['dest'])
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('consume')
            ->with(1000)
            ->willReturn($kafkaMessage)
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('unsubscribe')
        ;

        $consumer = new RdKafkaConsumer($kafkaConsumer, $this->createContextMock(), $destination);

        $this->assertNull($consumer->receive(1000));
    }

    public function testShouldReceiveFromQueueAndReturnMessageIfMessageInQueue()
    {
        $destination = new RdKafkaTopic('dest');

        $message = new  RdKafkaMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $kafkaMessage = new Message();
        $kafkaMessage->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $kafkaMessage->payload = json_encode($message);

        $kafkaConsumer = $this->createKafkaConsumerMock();
        $kafkaConsumer
            ->expects($this->once())
            ->method('subscribe')
            ->with(['dest'])
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('consume')
            ->with(1000)
            ->willReturn($kafkaMessage)
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('unsubscribe')
        ;

        $consumer = new RdKafkaConsumer($kafkaConsumer, $this->createContextMock(), $destination);

        $actualMessage = $consumer->receive(1000);

        $this->assertSame('theBody', $actualMessage->getBody());
        $this->assertSame(['foo' => 'fooVal'], $actualMessage->getProperties());
        $this->assertSame(['bar' => 'barVal'], $actualMessage->getHeaders());
        $this->assertSame($kafkaMessage, $actualMessage->getKafkaMessage());
    }

    public function testShouldReceiveNoWaitFromQueueAndReturnNullIfNoMessageInQueue()
    {
        $destination = new RdKafkaTopic('dest');

        $kafkaMessage = new Message();
        $kafkaMessage->err = RD_KAFKA_RESP_ERR__TIMED_OUT;

        $kafkaConsumer = $this->createKafkaConsumerMock();
        $kafkaConsumer
            ->expects($this->once())
            ->method('subscribe')
            ->with(['dest'])
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('consume')
            ->with(10)
            ->willReturn($kafkaMessage)
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('unsubscribe')
        ;

        $consumer = new RdKafkaConsumer($kafkaConsumer, $this->createContextMock(), $destination);

        $this->assertNull($consumer->receiveNoWait());
    }

    public function testShouldReceiveNoWaitFromQueueAndReturnMessageIfMessageInQueue()
    {
        $destination = new RdKafkaTopic('dest');

        $message = new  RdKafkaMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);

        $kafkaMessage = new Message();
        $kafkaMessage->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $kafkaMessage->payload = json_encode($message);

        $kafkaConsumer = $this->createKafkaConsumerMock();
        $kafkaConsumer
            ->expects($this->once())
            ->method('subscribe')
            ->with(['dest'])
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('consume')
            ->with(10)
            ->willReturn($kafkaMessage)
        ;
        $kafkaConsumer
            ->expects($this->once())
            ->method('unsubscribe')
        ;

        $consumer = new RdKafkaConsumer($kafkaConsumer, $this->createContextMock(), $destination);

        $actualMessage = $consumer->receiveNoWait();

        $this->assertSame('theBody', $actualMessage->getBody());
        $this->assertSame(['foo' => 'fooVal'], $actualMessage->getProperties());
        $this->assertSame(['bar' => 'barVal'], $actualMessage->getHeaders());
        $this->assertSame($kafkaMessage, $actualMessage->getKafkaMessage());
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
}
