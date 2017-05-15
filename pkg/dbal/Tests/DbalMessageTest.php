<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalMessage;
use Enqueue\Psr\PsrMessage;
use Enqueue\Test\ClassExtensionTrait;

class DbalMessageTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageInterface()
    {
        $this->assertClassImplements(PsrMessage::class, DbalMessage::class);
    }

    public function testCouldBeConstructedWithoutArguments()
    {
        $message = new DbalMessage();

        $this->assertNull($message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testCouldBeConstructedWithOptionalArguments()
    {
        $message = new DbalMessage('theBody', ['barProp' => 'barPropVal'], ['fooHeader' => 'fooHeaderVal']);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['barProp' => 'barPropVal'], $message->getProperties());
        $this->assertSame(['fooHeader' => 'fooHeaderVal'], $message->getHeaders());
    }

    public function testShouldSetRedeliveredToFalseInConstructor()
    {
        $message = new DbalMessage();

        $this->assertSame(false, $message->isRedelivered());
    }

    public function testShouldSetPriorityToZeroInConstructor()
    {
        $message = new DbalMessage();

        $this->assertSame(0, $message->getPriority());
    }

    public function testShouldSetDelayToNullInConstructor()
    {
        $message = new DbalMessage();

        $this->assertNull($message->getDelay());
    }

    public function testShouldReturnPreviouslySetBody()
    {
        $message = new DbalMessage();

        $message->setBody('theBody');

        $this->assertSame('theBody', $message->getBody());
    }

    public function testShouldReturnPreviouslySetProperties()
    {
        $message = new DbalMessage();

        $message->setProperties(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $message->getProperties());
    }

    public function testShouldReturnPreviouslySetProperty()
    {
        $message = new DbalMessage(null, ['foo' => 'fooVal']);

        $message->setProperty('bar', 'barVal');

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $message->getProperties());
    }

    public function testShouldReturnSinglePreviouslySetProperty()
    {
        $message = new DbalMessage();

        $this->assertSame(null, $message->getProperty('bar'));
        $this->assertSame('default', $message->getProperty('bar', 'default'));

        $message->setProperty('bar', 'barVal');
        $this->assertSame('barVal', $message->getProperty('bar'));
    }

    public function testShouldReturnPreviouslySetHeaders()
    {
        $message = new DbalMessage();

        $message->setHeaders(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetHeader()
    {
        $message = new DbalMessage(null, [], ['foo' => 'fooVal']);

        $message->setHeader('bar', 'barVal');

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $message->getHeaders());
    }

    public function testShouldReturnSinglePreviouslySetHeader()
    {
        $message = new DbalMessage();

        $this->assertSame(null, $message->getHeader('bar'));
        $this->assertSame('default', $message->getHeader('bar', 'default'));

        $message->setHeader('bar', 'barVal');
        $this->assertSame('barVal', $message->getHeader('bar'));
    }

    public function testShouldReturnPreviouslySetRedelivered()
    {
        $message = new DbalMessage();

        $message->setRedelivered(true);
        $this->assertSame(true, $message->isRedelivered());

        $message->setRedelivered(false);
        $this->assertSame(false, $message->isRedelivered());
    }

    public function testShouldReturnPreviouslySetCorrelationId()
    {
        $message = new DbalMessage();
        $message->setCorrelationId('theCorrelationId');

        $this->assertSame('theCorrelationId', $message->getCorrelationId());
        $this->assertSame(['correlation_id' => 'theCorrelationId'], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetMessageId()
    {
        $message = new DbalMessage();
        $message->setMessageId('theMessageId');

        $this->assertSame('theMessageId', $message->getMessageId());
        $this->assertSame(['message_id' => 'theMessageId'], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetTimestamp()
    {
        $message = new DbalMessage();
        $message->setTimestamp(12345);

        $this->assertSame(12345, $message->getTimestamp());
        $this->assertSame(['timestamp' => 12345], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetReplyTo()
    {
        $message = new DbalMessage();
        $message->setReplyTo('theReply');

        $this->assertSame('theReply', $message->getReplyTo());
        $this->assertSame(['reply_to' => 'theReply'], $message->getHeaders());
    }
}
