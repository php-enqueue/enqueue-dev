<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\OnPrepareMessage;
use PHPUnit\Framework\TestCase;

class OnPrepareMessageTest extends TestCase
{
    public function testShouldBeConstructedWithExpectedArguments()
    {
        new OnPrepareMessage(new Message(), 'aTopic', 'aCommand');
    }

    public function testShouldAllowGetMessageSetInConstructor()
    {
        $message = new Message();

        $context = new OnPrepareMessage($message, 'aTopic', 'aCommand');

        $this->assertSame($message, $context->getMessage());
    }

    public function testShouldAllowGetPreviouslySetMessage()
    {
        $context = new OnPrepareMessage(new Message(), 'aTopic', 'aCommand');

        $message = new Message();
        $context->setMessage($message);

        $this->assertSame($message, $context->getMessage());
    }

    public function testShouldAllowGetTopicSetInConstructor()
    {
        $topic = 'theTopic';

        $context = new OnPrepareMessage(new Message(), $topic, 'aCommand');

        $this->assertSame($topic, $context->getTopic());
    }

    public function testShouldAllowGetCommandSetInConstructor()
    {
        $command = 'theCommand';

        $context = new OnPrepareMessage(new Message(), 'aTopic', $command);

        $this->assertSame($command, $context->getCommand());
    }
}
