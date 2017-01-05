<?php
namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Psr\Consumer;
use Enqueue\Psr\Context as PsrContext;
use Enqueue\Psr\Processor;
use Psr\Log\LoggerInterface;

class LimitConsumptionTimeExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumptionTimeExtension(new \DateTime('+1 day'));
    }

    public function testOnBeforeReceiveShouldInterruptExecutionIfConsumptionTimeExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('-2 second'));

        $extension->onBeforeReceive($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnIdleShouldInterruptExecutionIfConsumptionTimeExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('-2 second'));

        $extension->onIdle($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfConsumptionTimeExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('-2 second'));

        $extension->onPostReceived($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceiveShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+2 second'));

        $extension->onBeforeReceive($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnIdleShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+2 second'));

        $extension->onIdle($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+2 second'));

        $extension->onPostReceived($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        $context = new Context($this->createMock(PsrContext::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setPsrConsumer($this->createMock(Consumer::class));
        $context->setPsrProcessor($this->createMock(Processor::class));

        return $context;
    }
}
