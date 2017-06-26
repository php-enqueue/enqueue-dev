<?php

namespace Enqueue\Psr\Spec;

use Enqueue\Psr\PsrTopic;
use PHPUnit\Framework\TestCase;

abstract class PsrTopicSpec extends TestCase
{
    const EXPECTED_TOPIC_NAME = 'theTopicName';

    public function testShouldImplementTopicInterface()
    {
        $this->assertInstanceOf(PsrTopic::class, $this->createTopic());
    }

    public function testShouldReturnTopicName()
    {
        $topic = $this->createTopic();

        $this->assertSame(self::EXPECTED_TOPIC_NAME, $topic->getTopicName());
    }

    /**
     * @return PsrTopic
     */
    abstract protected function createTopic();
}
