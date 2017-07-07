<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\ConsumptionExtension\SetupBrokerExtension;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SetupBrokerExtensionTest extends TestCase
{
    use ClassExtensionTrait;

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

        $context = new Context($this->createMock(PsrContext::class));
        $context->setLogger($logger);

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

        $context = new Context($this->createMock(PsrContext::class));
        $context->setLogger($logger);

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
