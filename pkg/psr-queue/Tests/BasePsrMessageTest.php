<?php

namespace Enqueue\Psr\Tests;

use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use PHPUnit\Framework\TestCase;

abstract class BasePsrMessageTest extends TestCase
{
    public function testShouldSetRedeliveredToFalseInConstructor()
    {
        $message = $this->createMessage();

        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldReturnEmptyStringIfNotPreviouslySetOnGetBody()
    {
        $message = $this->createMessage();

        $this->assertSame('', $message->getBody());
    }

    public function testShouldReturnPreviouslySetBody()
    {
        $message = $this->createMessage();

        $message->setBody('theBody');

        $this->assertSame('theBody', $message->getBody());
    }

    public function testShouldReturnEmptyArrayIfPropertiesNotPreviouslySetOnGetProperties()
    {
        $message = $this->createMessage();

        $this->assertSame([], $message->getProperties());
    }

    public function testShouldReturnPreviouslySetProperties()
    {
        $message = $this->createMessage();

        $message->setProperties(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $message->getProperties());
    }

    public function testShouldReturnPreviouslySetProperty()
    {
        $message = $this->createMessage();

        $message->setProperty('bar', 'barVal');

        $this->assertSame(['bar' => 'barVal'], $message->getProperties());
    }

    public function testShouldReturnSinglePreviouslySetProperty()
    {
        $message = $this->createMessage();

        $this->assertSame(null, $message->getProperty('bar'));
        $this->assertSame('default', $message->getProperty('bar', 'default'));

        $message->setProperty('bar', 'barVal');
        $this->assertSame('barVal', $message->getProperty('bar'));
    }

    public function testShouldReturnEmptyArrayIfHeadersNotPreviouslySetOnGetHeaders()
    {
        $message = $this->createMessage();

        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetHeaders()
    {
        $message = $this->createMessage();

        $message->setHeaders(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $message->getHeaders());
    }

    public function testShouldReturnPreviouslySetHeader()
    {
        $message = $this->createMessage();

        $message->setHeader('bar', 'barVal');

        $this->assertSame(['bar' => 'barVal'], $message->getHeaders());
    }

    public function testShouldReturnSinglePreviouslySetHeader()
    {
        $message = $this->createMessage();

        $this->assertSame(null, $message->getHeader('bar'));
        $this->assertSame('default', $message->getHeader('bar', 'default'));

        $message->setHeader('bar', 'barVal');
        $this->assertSame('barVal', $message->getHeader('bar'));
    }

    public function testShouldReturnFalseIfNotPreviouslySetOnIsRedelivered()
    {
        $message = $this->createMessage();

        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldReturnPreviouslySetRedelivered()
    {
        $message = $this->createMessage();

        $message->setRedelivered(true);
        $this->assertSame(true, $message->isRedelivered());

        $message->setRedelivered(false);
        $this->assertSame(false, $message->isRedelivered());
    }

    public function testShouldReturnNullIfNotPreviouslySetOnGetDestination()
    {
        $message = $this->createMessage();

        $this->assertNull($message->getDestination());
    }

    public function testShouldReturnPreviouslySetDestination()
    {
        $destinationMock = $this->createMock(PsrDestination::class);

        $message = $this->createMessage();

        $message->setDestination($destinationMock);

        $this->assertSame($destinationMock, $message->getDestination());
    }

    public function testShouldAllowUnsetPreviouslySetDestination()
    {
        $destinationMock = $this->createMock(PsrDestination::class);

        $message = $this->createMessage();

        $message->setDestination($destinationMock);

        // guard
        $this->assertSame($destinationMock, $message->getDestination());

        $message->setDestination(null);

        $this->assertNull($message->getDestination());
    }

    /**
     * @return PsrMessage
     */
    abstract protected function createMessage();
}
