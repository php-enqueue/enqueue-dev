<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Extension\NicenessExtension;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NicenessExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new NicenessExtension(0);
    }

    public function testShouldThrowExceptionOnInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        new NicenessExtension('1');
    }

    public function testShouldThrowWarningOnInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('proc_nice(): Only a super user may attempt to increase the priority of a process');

        $extension = new NicenessExtension(-1);
        $extension->onStart($this->createContext());
    }

    /**
     * @return Context
     */
    protected function createContext(): Context
    {
        $context = new Context($this->createMock(InteropContext::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setConsumer($this->createMock(Consumer::class));
        $context->setProcessor($this->createMock(Processor::class));

        return $context;
    }
}
