<?php

namespace Enqueue\Tests\Router;

use Enqueue\Router\Recipient;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{
    public function testShouldAllowGetMessageSetInConstructor()
    {
        $message = $this->createMock(PsrMessage::class);

        $recipient = new Recipient($this->createMock(PsrDestination::class), $message);

        $this->assertSame($message, $recipient->getMessage());
    }

    public function testShouldAllowGetDestinationSetInConstructor()
    {
        $destination = $this->createMock(PsrDestination::class);

        $recipient = new Recipient($destination, $this->createMock(PsrMessage::class));

        $this->assertSame($destination, $recipient->getDestination());
    }
}
