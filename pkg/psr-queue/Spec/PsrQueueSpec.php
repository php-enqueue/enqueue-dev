<?php

namespace Enqueue\Psr\Spec;

use Enqueue\Psr\PsrQueue;
use PHPUnit\Framework\TestCase;

abstract class PsrQueueSpec extends TestCase
{
    const EXPECTED_QUEUE_NAME = 'theQueueName';

    public function testShouldImplementQueueInterface()
    {
        $this->assertInstanceOf(PsrQueue::class, $this->createQueue());
    }

    public function testShouldReturnQueueName()
    {
        $queue = $this->createQueue();

        $this->assertSame(self::EXPECTED_QUEUE_NAME, $queue->getQueueName());
    }

    /**
     * @return PsrQueue
     */
    abstract protected function createQueue();
}
