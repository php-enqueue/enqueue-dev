<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\ConsumptionExtension\SetupBrokerExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\StartExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context as InteropContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SetupBrokerExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementStartExtensionInterface()
    {
        $this->assertClassImplements(StartExtensionInterface::class, SetupBrokerExtension::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SetupBrokerExtension($this->createDriverMock());
    }

    public function testShouldSetupBroker()
    {
        $logger = new NullLogger();

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('setupBroker')
            ->with($this->identicalTo($logger))
        ;

        $context = new Start($this->createMock(InteropContext::class), $logger, [], 0, 0);

        $extension = new SetupBrokerExtension($driver);
        $extension->onStart($context);
    }

    public function testShouldSetupBrokerOnlyOnce()
    {
        $logger = new NullLogger();

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('setupBroker')
            ->with($this->identicalTo($logger))
        ;

        $context = new Start($this->createMock(InteropContext::class), $logger, [], 0, 0);

        $extension = new SetupBrokerExtension($driver);
        $extension->onStart($context);
        $extension->onStart($context);
    }

    /**
     * @return MockObject|DriverInterface
     */
    private function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
