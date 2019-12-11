<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Extension\NicenessExtension;
use Interop\Queue\Context as InteropContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

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

        $context = new Start($this->createContextMock(), new NullLogger(), [], 0, 0);

        $extension = new NicenessExtension(-1);
        $extension->onStart($context);
    }

    /**
     * @return MockObject|InteropContext
     */
    protected function createContextMock(): InteropContext
    {
        return $this->createMock(InteropContext::class);
    }
}
