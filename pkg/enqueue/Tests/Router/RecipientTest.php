<?php

namespace Enqueue\Tests\Router;

use Enqueue\Router\Recipient;
use Interop\Queue\Destination;
use Interop\Queue\Message as InteropMessage;
use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{
    public function testShouldAllowGetMessageSetInConstructor()
    {
        $message = $this->createMock(InteropMessage::class);

        $recipient = new Recipient($this->createMock(Destination::class), $message);

        $this->assertSame($message, $recipient->getMessage());
    }

    public function testShouldAllowGetDestinationSetInConstructor()
    {
        $destination = $this->createMock(Destination::class);

        $recipient = new Recipient($destination, $this->createMock(InteropMessage::class));

        $this->assertSame($destination, $recipient->getDestination());
    }
}
