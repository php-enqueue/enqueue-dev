<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Consumption\StartExtensionInterface;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementStartExtensionInterface()
    {
        $this->assertClassImplements(StartExtensionInterface::class, LoggerExtension::class);
    }

    public function testShouldImplementPostMessageReceivedExtensionInterface()
    {
        $this->assertClassImplements(PostMessageReceivedExtensionInterface::class, LoggerExtension::class);
    }

    public function testCouldBeConstructedWithLoggerAsFirstArgument()
    {
        new LoggerExtension($this->createLogger());
    }

    public function testShouldSetLoggerToContextOnStart()
    {
        $logger = $this->createLogger();

        $extension = new LoggerExtension($logger);

        $context = new Start($this->createContextMock(), new NullLogger(), [], 0, 0);

        $extension->onStart($context);

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldAddInfoMessageOnStart()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Set context\'s logger'))
        ;

        $extension = new LoggerExtension($logger);

        $context = new Start($this->createContextMock(), new NullLogger(), [], 0, 0);

        $extension->onStart($context);
    }

    public function testShouldLogRejectMessageStatus()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('reason', ['body' => 'message body', 'headers' => [], 'properties' => []])
        ;

        $extension = new LoggerExtension($logger);

        $message = new NullMessage();
        $message->setBody('message body');

        $postReceivedMessage = new PostMessageReceived(
            $this->createContextMock(),
            $message,
            Result::reject('reason'),
            1,
            $logger
        );

        $extension->onPostMessageReceived($postReceivedMessage);
    }

    public function testShouldLogRequeueMessageStatus()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('reason', ['body' => 'message body', 'headers' => [], 'properties' => []])
        ;

        $extension = new LoggerExtension($logger);

        $message = new NullMessage();
        $message->setBody('message body');

        $postReceivedMessage = new PostMessageReceived(
            $this->createContextMock(),
            $message,
            Result::requeue('reason'),
            1,
            $logger
        );

        $extension->onPostMessageReceived($postReceivedMessage);
    }

    public function testShouldNotLogRequeueMessageStatusIfReasonIsEmpty()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $extension = new LoggerExtension($logger);

        $postReceivedMessage = new PostMessageReceived(
            $this->createContextMock(),
            $this->createMock(Message::class),
            Result::requeue(),
            1,
            $logger
        );

        $extension->onPostMessageReceived($postReceivedMessage);
    }

    public function testShouldLogAckMessageStatus()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('reason', ['body' => 'message body', 'headers' => [], 'properties' => []])
        ;

        $extension = new LoggerExtension($logger);

        $message = new NullMessage();
        $message->setBody('message body');

        $postReceivedMessage = new PostMessageReceived(
            $this->createContextMock(),
            $message,
            Result::ack('reason'),
            1,
            $logger
        );

        $extension->onPostMessageReceived($postReceivedMessage);
    }

    public function testShouldNotLogAckMessageStatusIfReasonIsEmpty()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->never())
            ->method('info')
        ;

        $extension = new LoggerExtension($logger);

        $postReceivedMessage = new PostMessageReceived(
            $this->createContextMock(),
            $this->createMock(Message::class),
            Result::ack(),
            1,
            $logger
        );

        $extension->onPostMessageReceived($postReceivedMessage);
    }

    public function testShouldNotSetLoggerIfOneHasBeenSetOnStart()
    {
        $logger = $this->createLogger();

        $alreadySetLogger = $this->createLogger();
        $alreadySetLogger
            ->expects($this->once())
            ->method('debug')
            ->with(sprintf(
                'Skip setting context\'s logger "%s". Another one "%s" has already been set.',
                get_class($logger),
                get_class($alreadySetLogger)
            ))
        ;

        $extension = new LoggerExtension($logger);

        $context = new Start($this->createContextMock(), $alreadySetLogger, [], 0, 0);

        $extension->onStart($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InteropContext
     */
    protected function createContextMock(): InteropContext
    {
        return $this->createMock(InteropContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLogger()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Consumer
     */
    protected function createConsumerMock()
    {
        return $this->createMock(Consumer::class);
    }
}
