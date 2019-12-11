<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context\InitLogger;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\InitLoggerExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementInitLoggerExtensionInterface()
    {
        $this->assertClassImplements(InitLoggerExtensionInterface::class, LoggerExtension::class);
    }

    public function testCouldBeConstructedWithLoggerAsFirstArgument()
    {
        new LoggerExtension($this->createLogger());
    }

    public function testShouldSetLoggerToContextOnInitLogger()
    {
        $logger = $this->createLogger();

        $extension = new LoggerExtension($logger);

        $previousLogger = new NullLogger();
        $context = new InitLogger($previousLogger);

        $extension->onInitLogger($context);

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldAddInfoMessageOnStart()
    {
        $previousLogger = $this->createLogger();

        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with(sprintf('Change logger from "%s" to "%s"', get_class($logger), get_class($previousLogger)))
        ;

        $extension = new LoggerExtension($logger);

        $context = new InitLogger($previousLogger);

        $extension->onInitLogger($context);
    }

    public function testShouldDoNothingIfSameLoggerInstanceAlreadySet()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->never())
            ->method('debug')
        ;

        $extension = new LoggerExtension($logger);

        $context = new InitLogger($logger);

        $extension->onInitLogger($context);

        $this->assertSame($logger, $context->getLogger());
    }

    /**
     * @return MockObject|LoggerInterface
     */
    protected function createLogger()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
