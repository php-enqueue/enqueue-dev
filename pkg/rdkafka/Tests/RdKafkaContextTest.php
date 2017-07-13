<?php
namespace Enqueue\RdKafka\Tests;

use Enqueue\Null\NullQueue;
use Enqueue\RdKafka\RdKafkaContext;
use Interop\Queue\InvalidDestinationException;
use PHPUnit\Framework\TestCase;

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
}
