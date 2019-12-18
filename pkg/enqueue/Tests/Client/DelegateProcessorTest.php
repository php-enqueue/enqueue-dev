<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Null\NullMessage;
use Enqueue\ProcessorRegistryInterface;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DelegateProcessorTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DelegateProcessor($this->createProcessorRegistryMock());
    }

    public function testShouldThrowExceptionIfProcessorNameIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Got message without required parameter: "enqueue.processor"');

        $processor = new DelegateProcessor($this->createProcessorRegistryMock());
        $processor->process(new NullMessage(), $this->createContextMock());
    }

    public function testShouldProcessMessage()
    {
        $session = $this->createContextMock();
        $message = new NullMessage();
        $message->setProperties([
            Config::PROCESSOR => 'processor-name',
        ]);

        $processor = $this->createProcessorMock();
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($message), $this->identicalTo($session))
            ->willReturn('return-value')
        ;

        $processorRegistry = $this->createProcessorRegistryMock();
        $processorRegistry
            ->expects($this->once())
            ->method('get')
            ->with('processor-name')
            ->willReturn($processor)
        ;

        $processor = new DelegateProcessor($processorRegistry);
        $return = $processor->process($message, $session);

        $this->assertEquals('return-value', $return);
    }

    /**
     * @return MockObject|ProcessorRegistryInterface
     */
    protected function createProcessorRegistryMock()
    {
        return $this->createMock(ProcessorRegistryInterface::class);
    }

    /**
     * @return MockObject|Context
     */
    protected function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return MockObject|Processor
     */
    protected function createProcessorMock()
    {
        return $this->createMock(Processor::class);
    }
}
