<?php

namespace Enqueue\NoEffect\Tests;

use Enqueue\NoEffect\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;

class NullMessageTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(Message::class, NullMessage::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        $message = new NullMessage();

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new NullMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }
}
