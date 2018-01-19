<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\OnSend;
use PHPUnit\Framework\TestCase;

class OnSendTest extends TestCase
{
    public function testShouldBeConstructedWithExpectedArguments()
    {
        new OnSend(new Message(), 'aTopic', 'aCommand');
    }

    public function testShouldAllowGetMessageSetInConstructor()
    {
        $message = new Message();

        $context = new OnSend($message, 'aTopic', 'aCommand');

        $this->assertSame($message, $context->getMessage());
    }

    public function testShouldAllowGetTopicSetInConstructor()
    {
        $topic = 'theTopic';

        $context = new OnSend(new Message(), $topic, 'aCommand');

        $this->assertSame($topic, $context->getTopic());
    }

    public function testShouldAllowGetCommandSetInConstructor()
    {
        $command = 'theCommand';

        $context = new OnSend(new Message(), 'aTopic', $command);

        $this->assertSame($command, $context->getCommand());
    }
}
