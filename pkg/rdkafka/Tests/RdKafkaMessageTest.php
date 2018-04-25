<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\RdKafkaMessage;
use PHPUnit\Framework\TestCase;
use RdKafka\Message;

/**
 * @group rdkafka
 */
class RdKafkaMessageTest extends TestCase
{
    public function testCouldSetGetPartition()
    {
        $message = new RdKafkaMessage();
        $message->setPartition(5);

        $this->assertSame(5, $message->getPartition());
    }

    public function testCouldSetGetKey()
    {
        $message = new RdKafkaMessage();
        $message->setKey('key');

        $this->assertSame('key', $message->getKey());
    }

    public function testCouldSetGetKafkaMessage()
    {
        $message = new RdKafkaMessage();
        $message->setKafkaMessage($kafkaMessage = $this->createMock(Message::class));

        $this->assertSame($kafkaMessage, $message->getKafkaMessage());
    }
}
