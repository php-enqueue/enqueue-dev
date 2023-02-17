<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsSubscribe;
use Enqueue\SnsQs\SnsQsContext;
use Enqueue\SnsQs\SnsQsQueue;
use Enqueue\SnsQs\SnsQsTopic;
use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsContext;
use Interop\Queue\Spec\ContextSpec;

class SnsQsContextTest extends ContextSpec
{
    public function testSetsSubscriptionAttributes(): void
    {
        $topic = new SnsQsTopic('topic1');

        $snsContext = $this->createMock(SnsContext::class);
        $snsContext->expects($this->once())
            ->method('setSubscriptionAttributes')
            ->with($this->equalTo(new SnsSubscribe(
                $topic,
                'queueArn1',
                'sqs',
                false,
                ['attr1' => 'value1'],
            )));

        $sqsContext = $this->createMock(SqsContext::class);
        $sqsContext->expects($this->any())
            ->method('createConsumer')
            ->willReturn($this->createMock(SqsConsumer::class));
        $sqsContext->expects($this->any())
            ->method('getQueueArn')
            ->willReturn('queueArn1');

        $context = new SnsQsContext($snsContext, $sqsContext);
        $context->setSubscriptionAttributes(
            $topic,
            new SnsQsQueue('queue1'),
            ['attr1' => 'value1'],
        );
    }

    protected function createContext()
    {
        $sqsContext = $this->createMock(SqsContext::class);
        $sqsContext
            ->expects($this->any())
            ->method('createConsumer')
            ->willReturn($this->createMock(SqsConsumer::class))
        ;

        return new SnsQsContext(
            $this->createMock(SnsContext::class),
            $sqsContext
        );
    }
}
