<?php

declare(strict_types=1);

namespace Enqueue\SnsQs\Tests;

use Enqueue\SnsQs\SnsQsConsumer;
use Enqueue\SnsQs\SnsQsContext;
use Enqueue\SnsQs\SnsQsMessage;
use Enqueue\SnsQs\SnsQsQueue;
use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsMessage;
use PHPUnit\Framework\TestCase;

final class SnsQsConsumerTest extends TestCase
{
    public function testReceivesSnsMessage(): void
    {
        $context = $this->createMock(SnsQsContext::class);
        $context->expects($this->once())
            ->method('createMessage')
            ->willReturn(new SnsQsMessage());

        $sqsConsumer = $this->createMock(SqsConsumer::class);
        $sqsConsumer->expects($this->once())
            ->method('receive')
            ->willReturn(new SqsMessage(json_encode([
                'Type' => 'Notification',
                'TopicArn' => 'arn:aws:sns:us-east-2:12345:topic-name',
                'Message' => 'The Body',
                'MessageAttributes' => [
                    'Headers' => [
                        'Type' => 'String',
                        'Value' => base64_encode('[{"headerKey":"headerVal"},{"propKey": "propVal"}]'),
                    ],
                ],
            ])));

        $consumer = new SnsQsConsumer($context, $sqsConsumer, new SnsQsQueue('queue'));
        $result = $consumer->receive();

        $this->assertInstanceOf(SnsQsMessage::class, $result);
        $this->assertSame('The Body', $result->getBody());
        $this->assertSame(['headerKey' => 'headerVal'], $result->getHeaders());
        $this->assertSame(['propKey' => 'propVal'], $result->getProperties());
    }

    public function testReceivesSnsMessageWithUnencodedHeaders(): void
    {
        $context = $this->createMock(SnsQsContext::class);
        $context->expects($this->once())
            ->method('createMessage')
            ->willReturn(new SnsQsMessage());

        $sqsConsumer = $this->createMock(SqsConsumer::class);
        $sqsConsumer->expects($this->once())
            ->method('receive')
            ->willReturn(new SqsMessage(json_encode([
                'Type' => 'Notification',
                'TopicArn' => 'arn:aws:sns:us-east-2:12345:topic-name',
                'Message' => 'The Body',
                'MessageAttributes' => [
                    'Headers' => [
                        'Type' => 'String',
                        'Value' => '[{"headerKey":"headerVal"},{"propKey": "propVal"}]',
                    ],
                ],
            ])));

        $consumer = new SnsQsConsumer($context, $sqsConsumer, new SnsQsQueue('queue'));
        $result = $consumer->receive();

        $this->assertInstanceOf(SnsQsMessage::class, $result);
        $this->assertSame('The Body', $result->getBody());
        $this->assertSame(['headerKey' => 'headerVal'], $result->getHeaders());
        $this->assertSame(['propKey' => 'propVal'], $result->getProperties());
    }

    public function testReceivesSqsMessage(): void
    {
        $context = $this->createMock(SnsQsContext::class);
        $context->expects($this->once())
            ->method('createMessage')
            ->willReturn(new SnsQsMessage());

        $sqsConsumer = $this->createMock(SqsConsumer::class);
        $sqsConsumer->expects($this->once())
            ->method('receive')
            ->willReturn(new SqsMessage(
                'The Body',
                ['propKey' => 'propVal'],
                ['headerKey' => 'headerVal'],
            ));

        $consumer = new SnsQsConsumer($context, $sqsConsumer, new SnsQsQueue('queue'));
        $result = $consumer->receive();

        $this->assertInstanceOf(SnsQsMessage::class, $result);
        $this->assertSame('The Body', $result->getBody());
        $this->assertSame(['headerKey' => 'headerVal'], $result->getHeaders());
        $this->assertSame(['propKey' => 'propVal'], $result->getProperties());
    }
}
