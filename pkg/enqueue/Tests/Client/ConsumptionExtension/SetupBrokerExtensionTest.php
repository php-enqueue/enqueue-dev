<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\ConsumptionExtension\SetupBrokerExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\OnStartContext;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ConsumptionContextMockTrait;
use Interop\Queue\PsrContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SetupBrokerExtensionTest extends TestCase
{
    use ClassExtensionTrait;
    use ConsumptionContextMockTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, SetupBrokerExtension::class);
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

        $context = new OnStartContext($this->createMock(PsrContext::class), $logger, [], []);

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

        $context = new OnStartContext($this->createMock(PsrContext::class), $logger, [], []);

        $extension = new SetupBrokerExtension($driver);
        $extension->onStart($context);
        $extension->onStart($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private function createDriverMock()
    {
        return $this->createMock(DriverInterface::class);
    }
}
