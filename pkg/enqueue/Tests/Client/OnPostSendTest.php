<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\OnPostSend;
use PHPUnit\Framework\TestCase;

class OnPostSendTest extends TestCase
{
    public function testShouldBeConstructedWithExpectedArguments()
    {
        new OnPostSend(new Message(), 'aTopic', 'aCommand');
    }

    public function testShouldAllowGetMessageSetInConstructor()
    {
        $message = new Message();

        $context = new OnPostSend($message, 'aTopic', 'aCommand');

        $this->assertSame($message, $context->getMessage());
    }

    public function testShouldAllowGetTopicSetInConstructor()
    {
        $topic = 'theTopic';

        $context = new OnPostSend(new Message(), $topic, 'aCommand');

        $this->assertSame($topic, $context->getTopic());
    }

    public function testShouldAllowGetCommandSetInConstructor()
    {
        $command = 'theCommand';

        $context = new OnPostSend(new Message(), 'aTopic', $command);

        $this->assertSame($command, $context->getCommand());
    }
}
