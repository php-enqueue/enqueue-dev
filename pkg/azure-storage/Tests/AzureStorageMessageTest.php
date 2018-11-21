<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage\Tests;

use Enqueue\Test\ClassExtensionTrait;
use Enqueue\AzureStorage\AzureStorageMessage;
use Interop\Queue\Spec\MessageSpec;
use Interop\Queue\Message;

class AzureStorageMessageTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(Message::class, AzureStorageMessage::class);
    }

    public function testCouldConstructMessageWithoutArguments()
    {
        $message = new AzureStorageMessage();

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new AzureStorageMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }

    public function testShouldSetCorrelationIdAsHeader()
    {
        $message = new AzureStorageMessage();
        $message->setCorrelationId('the-correlation-id');

        $this->assertSame(['correlation_id' => 'the-correlation-id'], $message->getHeaders());
    }

    public function testCouldSetMessageIdAsHeader()
    {
        $message = new AzureStorageMessage();
        $message->setMessageId('the-message-id');

        $this->assertSame(['message_id' => 'the-message-id'], $message->getHeaders());
    }

    public function testCouldSetTimestampAsHeader()
    {
        $message = new AzureStorageMessage();
        $message->setTimestamp(12345);

        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldSetReplyToAsHeader()
    {
        $message = new AzureStorageMessage();
        $message->setReplyTo('theQueueName');

        $this->assertSame(['reply_to' => 'theQueueName'], $message->getHeaders());
    }

}