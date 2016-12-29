<?php
namespace Enqueue\Tests\Client;

use Enqueue\Psr\Context;
use Enqueue\Client\Config;
use Enqueue\Client\DelegateMessageProcessor;
use Enqueue\Client\MessageProcessorRegistryInterface;
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Transport\Null\NullMessage;

class DelegateMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DelegateMessageProcessor($this->createMessageProcessorRegistryMock());
    }

    public function testShouldThrowExceptionIfProcessorNameIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Got message without required parameter: "enqueue.processor_name"'
        );

        $processor = new DelegateMessageProcessor($this->createMessageProcessorRegistryMock());
        $processor->process(new NullMessage(), $this->createPsrContextMock());
    }

    public function testShouldProcessMessage()
    {
        $session = $this->createPsrContextMock();
        $message = new NullMessage();
        $message->setProperties([
            Config::PARAMETER_PROCESSOR_NAME => 'processor-name',
        ]);

        $messageProcessor = $this->createMessageProcessorMock();
        $messageProcessor
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($message), $this->identicalTo($session))
            ->will($this->returnValue('return-value'))
        ;

        $processorRegistry = $this->createMessageProcessorRegistryMock();
        $processorRegistry
            ->expects($this->once())
            ->method('get')
            ->with('processor-name')
            ->will($this->returnValue($messageProcessor))
        ;

        $processor = new DelegateMessageProcessor($processorRegistry);
        $return = $processor->process($message, $session);

        $this->assertEquals('return-value', $return);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorRegistryInterface
     */
    protected function createMessageProcessorRegistryMock()
    {
        return $this->createMock(MessageProcessorRegistryInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createPsrContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorInterface
     */
    protected function createMessageProcessorMock()
    {
        return $this->createMock(MessageProcessorInterface::class);
    }
}
