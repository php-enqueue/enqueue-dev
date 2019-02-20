<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Enqueue\Sns\SnsContext;
use Enqueue\SnsQs\SnsQsContext;
use Enqueue\Sqs\SqsConsumer;
use Enqueue\Sqs\SqsContext;
use Interop\Queue\Spec\ContextSpec;

class SnsQsContextTest extends ContextSpec
{
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
