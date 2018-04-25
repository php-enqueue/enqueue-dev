<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class ChainExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, ChainExtension::class);
    }

    public function testCouldBeConstructedWithExtensionsArray()
    {
        new ChainExtension([$this->createExtension(), $this->createExtension()]);
    }

    public function testShouldProxyOnStartToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onStart($context);
    }

    public function testShouldProxyOnBeforeReceiveToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onBeforeReceive($context);
    }

    public function testShouldProxyOnPreReceiveToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPreReceived($context);
    }

    public function testShouldProxyOnResultToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onResult')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onResult')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onResult($context);
    }

    public function testShouldProxyOnPostReceiveToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPostReceived($context);
    }

    public function testShouldProxyOnIdleToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onIdle($context);
    }

    public function testShouldProxyOnInterruptedToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onInterrupted($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionInterface
     */
    protected function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }
}
