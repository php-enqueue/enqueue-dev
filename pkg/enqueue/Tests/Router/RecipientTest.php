<?php

namespace Enqueue\Tests\Router;

use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use Enqueue\Router\Recipient;
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
