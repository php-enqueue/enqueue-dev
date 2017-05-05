<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\ChainExtension;
use Enqueue\Client\ExtensionInterface;
use Enqueue\Client\Message;
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

    public function testShouldProxyOnPreSendToAllInternalExtensions()
    {
        $message = new Message();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPreSend')
            ->with('topic', $this->identicalTo($message))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPreSend')
            ->with('topic', $this->identicalTo($message))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPreSend('topic', $message);
    }

    public function testShouldProxyOnPostSendToAllInternalExtensions()
    {
        $message = new Message();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPostSend')
            ->with('topic', $this->identicalTo($message))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPostSend')
            ->with('topic', $this->identicalTo($message))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPostSend('topic', $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionInterface
     */
    protected function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }
}
