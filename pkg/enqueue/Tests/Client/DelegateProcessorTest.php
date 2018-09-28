<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\ProcessorRegistryInterface;
use Enqueue\Null\NullMessage;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;

class DelegateProcessorTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DelegateProcessor($this->createProcessorRegistryMock());
    }

    public function testShouldThrowExceptionIfProcessorNameIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Got message without required parameter: "enqueue.processor"'
        );

        $processor = new DelegateProcessor($this->createProcessorRegistryMock());
        $processor->process(new NullMessage(), $this->createContextMock());
    }

    public function testShouldProcessMessage()
    {
        $session = $this->createContextMock();
        $message = new NullMessage();
        $message->setProperties([
            Config::PROCESSOR_PARAMETER => 'processor-name',
        ]);

        $processor = $this->createProcessorMock();
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($message), $this->identicalTo($session))
            ->will($this->returnValue('return-value'))
        ;

        $processorRegistry = $this->createProcessorRegistryMock();
        $processorRegistry
            ->expects($this->once())
            ->method('get')
            ->with('processor-name')
            ->will($this->returnValue($processor))
        ;

        $processor = new DelegateProcessor($processorRegistry);
        $return = $processor->process($message, $session);

        $this->assertEquals('return-value', $return);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProcessorRegistryInterface
     */
    protected function createProcessorRegistryMock()
    {
        return $this->createMock(ProcessorRegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Processor
     */
    protected function createProcessorMock()
    {
        return $this->createMock(Processor::class);
    }
}
