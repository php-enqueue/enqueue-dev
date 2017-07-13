<?php
namespace Enqueue\RdKafka\Tests;

use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\RdKafkaProducer;
use Enqueue\RdKafka\RdKafkaTopic;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use PHPUnit\Framework\TestCase;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use RdKafka\TopicConf;

class RdKafkaProducerTest extends TestCase
{
    public function testCouldBeConstructedWithKafkaProducerAsFirstArgument()
    {
        new RdKafkaProducer($this->createKafkaProducerMock());
    }

    public function testThrowIfDestinationInvalid()
    {
        $producer = new RdKafkaProducer($this->createKafkaProducerMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\RdKafka\RdKafkaTopic but got Enqueue\Null\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new RdKafkaMessage());
    }

    public function testThrowIfMessageInvalid()
    {
        $producer = new RdKafkaProducer($this->createKafkaProducerMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\RdKafka\RdKafkaMessage but it is Enqueue\Null\NullMessage.');
        $producer->send(new RdKafkaTopic('aQueue'), new NullMessage());
    }

    public function testShouldJsonEncodeMessageAndPutToExpectedTube()
    {
        $message = new RdKafkaMessage('theBody', ['foo' => 'fooVal'], ['bar' => 'barVal']);
        $message->setKey('key');

        $kafkaTopic = $this->createKafkaTopicMock();
        $kafkaTopic
            ->expects($this->once())
            ->method('produce')
            ->with(
                RD_KAFKA_PARTITION_UA,
                0,
                '{"body":"theBody","properties":{"foo":"fooVal"},"headers":{"bar":"barVal"}}',
                'key'
            )
        ;

        $kafkaProducer = $this->createKafkaProducerMock();
        $kafkaProducer
            ->expects($this->once())
            ->method('newTopic')
            ->with('theQueueName', $this->isInstanceOf(TopicConf::class))
            ->willReturn($kafkaTopic)
        ;

        $producer = new RdKafkaProducer($kafkaProducer);

        $producer->send(new RdKafkaTopic('theQueueName'), $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerTopic
     */
    private function createKafkaTopicMock()
    {
        return $this->createMock(ProducerTopic::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Producer
     */
    private function createKafkaProducerMock()
    {
        return $this->createMock(Producer::class);
    }
}
