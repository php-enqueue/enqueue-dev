<?php

namespace Enqueue\Sqs\Tests;

use Enqueue\Sqs\SqsMessage;
use Enqueue\Test\ClassExtensionTrait;

class SqsMessageTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithoutArguments()
    {
        $message = new SqsMessage();

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getAttributes());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new SqsMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new SqsMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testShouldSetMessageIdAsHeader()
    {
        $message = new SqsMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
    }

    public function testShouldSetTimestampAsHeader()
    {
        $message = new SqsMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldSetReplyToAsHeader()
    {
        $message = new SqsMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame(['reply_to' => 'theQueueName'], $message->getHeaders());
    }

    public function testShouldAllowGetDelaySeconds()
    {
        $message = new SqsMessage();
        $message->setDelaySeconds(12345);

        $this->assertSame(12345, $message->getDelaySeconds());
    }

    public function testShouldAllowGetMessageDeduplicationId()
    {
        $message = new SqsMessage();
        $message->setMessageDeduplicationId('theId');

        $this->assertSame('theId', $message->getMessageDeduplicationId());
    }

    public function testShouldAllowGetMessageGroupId()
    {
        $message = new SqsMessage();
        $message->setMessageGroupId('theId');

        $this->assertSame('theId', $message->getMessageGroupId());
    }

    public function testShouldAllowGetReceiptHandle()
    {
        $message = new SqsMessage();
        $message->setReceiptHandle('theId');

        $this->assertSame('theId', $message->getReceiptHandle());
    }

    public function testShouldAllowSettingAndGettingAttributes()
    {
        $message = new SqsMessage();
        $message->setAttributes($attributes = [
            'SenderId' => 'AROAX5IAWYILCTYIS3OZ5:foo@bar.com',
            'ApproximateFirstReceiveTimestamp' => '1560512269481',
            'ApproximateReceiveCount' => '2',
            'SentTimestamp' => '1560512260079',
        ]);

        $this->assertSame($attributes, $message->getAttributes());
        $this->assertSame($attributes['SenderId'], $message->getAttribute('SenderId'));
    }
}
