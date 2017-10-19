<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\RdKafkaTopic;
use PHPUnit\Framework\TestCase;
use RdKafka\TopicConf;

/**
 * @group rdkafka
 */
class RdKafkaTopicTest extends TestCase
{
    public function testCouldSetGetPartition()
    {
        $topic = new RdKafkaTopic('topic');
        $topic->setPartition(5);

        $this->assertSame(5, $topic->getPartition());
    }

    public function testCouldSetGetKey()
    {
        $topic = new RdKafkaTopic('topic');
        $topic->setKey('key');

        $this->assertSame('key', $topic->getKey());
    }

    public function testShouldReturnConfInstance()
    {
        $topic = new RdKafkaTopic('topic');

        $this->assertInstanceOf(TopicConf::class, $topic->getConf());
    }
}
