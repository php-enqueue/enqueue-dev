<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\ConsumptionExtension\DelayRedeliveredMessageExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Interop\Queue\PsrContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DelayRedeliveredMessageExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DelayRedeliveredMessageExtension($this->createDriverMock(), 12345);
    }

    public function testShouldSendDelayedMessageAndRejectOriginalMessage()
    {
        $queue = new NullQueue('queue');

        $originMessage = new NullMessage();
        $originMessage->setRedelivered(true);
        $originMessage->setBody('theBody');
        $originMessage->setHeaders(['foo' => 'fooVal']);
        $originMessage->setProperties(['bar' => 'barVal']);

        /** @var Message $delayedMessage */
        $delayedMessage = new Message();

        $driver = $this->createDriverMock();
        $driver
            ->expects(self::once())
            ->method('sendToProcessor')
            ->with(self::isInstanceOf(Message::class))
        ;
        $driver
            ->expects(self::once())
            ->method('createClientMessage')
            ->with(self::identicalTo($originMessage))
            ->willReturn($delayedMessage)
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects(self::at(0))
            ->method('debug')
            ->with('[DelayRedeliveredMessageExtension] Send delayed message')
        ;
        $logger
            ->expects(self::at(1))
            ->method('debug')
            ->with(
                '[DelayRedeliveredMessageExtension] '.
                'Reject redelivered original message by setting reject status to context.'
            )
        ;

        $context = new Context($this->createPsrContextMock());
        $context->setPsrQueue($queue);
        $context->setPsrMessage($originMessage);
        $context->setLogger($logger);

        $this->assertNull($context->getResult());

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        $result = $context->getResult();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame(Result::REJECT, $result->getStatus());
        $this->assertSame('A new copy of the message was sent with a delay. The original message is rejected', $result->getReason());

        $this->assertInstanceOf(Message::class, $delayedMessage);
        $this->assertEquals([
            'enqueue.redelivery_count' => 1,
        ], $delayedMessage->getProperties());
    }

    public function testShouldDoNothingIfMessageIsNotRedelivered()
    {
        $message = new NullMessage();

        $driver = $this->createDriverMock();
        $driver
            ->expects(self::never())
            ->method('sendToProcessor')
        ;

        $context = new Context($this->createPsrContextMock());
        $context->setPsrMessage($message);

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        $this->assertNull($context->getResult());
    }

    public function testShouldDoNothingIfMessageIsRedeliveredButResultWasAlreadySetOnContext()
    {
        $message = new NullMessage();
        $message->setRedelivered(true);

        $driver = $this->createDriverMock();
        $driver
            ->expects(self::never())
            ->method('sendToProcessor')
        ;

        $context = new Context($this->createPsrContextMock());
        $context->setPsrMessage($message);
        $context->setResult('aStatus');

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
