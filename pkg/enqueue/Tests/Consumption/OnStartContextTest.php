<?php
namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\OnStartContext;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class OnStartContextTest extends TestCase
{
    public function testCouldBeConstructedWithExpectedArguments()
    {
        new OnStartContext($this->createPsrContextMock(), new NullLogger(), [], []);
    }

    public function testShouldAllowGetPsrContextSetInConstructor()
    {
        $psrContext = $this->createPsrContextMock();

        $context = new OnStartContext($psrContext, new NullLogger(), [], []);

        $this->assertSame($psrContext, $context->getPsrContext());
    }

    public function testShouldAllowGetLoggerSetInConstructor()
    {
        $logger = new NullLogger();

        $context = new OnStartContext($this->createPsrContextMock(), $logger, [], []);

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldAllowGetPreviouslySetLogger()
    {
        $logger = new NullLogger();
        $anotherLogger = new NullLogger();

        $context = new OnStartContext($this->createPsrContextMock(), $logger, [], []);
        $context->setLogger($anotherLogger);

        $this->assertSame($anotherLogger, $context->getLogger());
    }

    public function testShouldAllowGetProcessorsSetInConstructor()
    {
        $processors = [
            'aFooQueue' => $this->createPsrProcessorMock(),
            'aBarQueue' => function(PsrMessage $message) {},
        ];

        $context = new OnStartContext($this->createPsrContextMock(), new NullLogger(), $processors, []);

        $this->assertSame($processors, $context->getProcessors());
    }

    public function testShouldAllowGetPreviouslySetProcessors()
    {
        $processors = [
            'aFooQueue' => $this->createPsrProcessorMock(),
            'aBarQueue' => function(PsrMessage $message) {},
        ];

        $anotherProcessors = [
            'aBazQueue' => $this->createPsrProcessorMock()
        ];

        $context = new OnStartContext($this->createPsrContextMock(), new NullLogger(), $processors, []);

        $context->setProcessors($anotherProcessors);

        $this->assertSame($anotherProcessors, $context->getProcessors());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    protected function createPsrContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProcessor
     */
    protected function createPsrProcessorMock()
    {
        return $this->createMock(PsrProcessor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrConsumer
     */
    protected function createPsrConsumerMock()
    {
        return $this->createMock(PsrConsumer::class);
    }
}