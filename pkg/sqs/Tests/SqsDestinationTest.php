<?php

namespace Enqueue\Sqs\Tests;

use Enqueue\Sqs\SqsDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Queue;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;

class SqsDestinationTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsTopicAndQueueInterfaces()
    {
        $this->assertClassImplements(Topic::class, SqsDestination::class);
        $this->assertClassImplements(Queue::class, SqsDestination::class);
    }

    public function testShouldReturnNameSetInConstructor()
    {
        $destination = new SqsDestination('aDestinationName');

        $this->assertSame('aDestinationName', $destination->getQueueName());
        $this->assertSame('aDestinationName', $destination->getTopicName());
    }

    public function testCouldSetDelaySecondsAttribute()
    {
        $destination = new SqsDestination('aDestinationName');
        $destination->setDelaySeconds(12345);

        $this->assertSame(['DelaySeconds' => 12345], $destination->getAttributes());
    }

    public function testCouldSetMaximumMessageSizeAttribute()
    {
        $destination = new SqsDestination('aDestinationName');
        $destination->setMaximumMessageSize(12345);

        $this->assertSame(['MaximumMessageSize' => 12345], $destination->getAttributes());
    }

    public function testCouldSetMessageRetentionPeriodAttribute()
    {
        $destination = new SqsDestination('aDestinationName');
        $destination->setMessageRetentionPeriod(12345);

        $this->assertSame(['MessageRetentionPeriod' => 12345], $destination->getAttributes());
    }

    public function testCouldSetPolicyAttribute()
    {
        $destination = new SqsDestination('aDestinationName');
        $destination->setPolicy('thePolicy');

        $this->assertSame(['Policy' => 'thePolicy'], $destination->getAttributes());
    }

    public function testCouldSetReceiveMessageWaitTimeSecondsAttribute()
    {
        $destination = new SqsDestination('aDestinationName');
        $destination->setReceiveMessageWaitTimeSeconds(12345);

        $this->assertSame(['ReceiveMessageWaitTimeSeconds' => 12345], $destination->getAttributes());
    }

    public function testCouldSetRedrivePolicyAttribute()
    {
        $destination = new SqsDestination('aDestinationName');
        $destination->setRedrivePolicy(12345, 'theDeadQueueArn');

        $this->assertSame(['RedrivePolicy' => '{"maxReceiveCount":"12345","deadLetterTargetArn":"theDeadQueueArn"}'], $destination->getAttributes());
    }

    public function testCouldSetVisibilityTimeoutAttribute()
    {
        $destination = new SqsDestination('aDestinationName');
        $destination->setVisibilityTimeout(12345);

        $this->assertSame(['VisibilityTimeout' => 12345], $destination->getAttributes());
    }

    public function testCouldSetFifoQueueAttributeAndUnsetIt()
    {
        $destination = new SqsDestination('aDestinationName');

        $destination->setFifoQueue(true);
        $this->assertSame(['FifoQueue' => 'true'], $destination->getAttributes());

        $destination->setFifoQueue(false);
        $this->assertSame([], $destination->getAttributes());
    }

    public function testCouldSetContentBasedDeduplicationAttributeAndUnsetIt()
    {
        $destination = new SqsDestination('aDestinationName');

        $destination->setContentBasedDeduplication(true);
        $this->assertSame(['ContentBasedDeduplication' => 'true'], $destination->getAttributes());

        $destination->setContentBasedDeduplication(false);
        $this->assertSame([], $destination->getAttributes());
    }
}
