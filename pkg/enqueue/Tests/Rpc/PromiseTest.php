<?php

namespace Enqueue\Tests\Rpc;

use Enqueue\Null\NullMessage;
use Enqueue\Rpc\Promise;
use PHPUnit\Framework\TestCase;

class PromiseTest extends TestCase
{
    public function testIsDeleteReplyQueueShouldReturnTrueByDefault()
    {
        $promise = new Promise(function () {}, function () {}, function () {});

        $this->assertTrue($promise->isDeleteReplyQueue());
    }

    public function testCouldSetGetDeleteReplyQueue()
    {
        $promise = new Promise(function () {}, function () {}, function () {});

        $promise->setDeleteReplyQueue(false);
        $this->assertFalse($promise->isDeleteReplyQueue());

        $promise->setDeleteReplyQueue(true);
        $this->assertTrue($promise->isDeleteReplyQueue());
    }

    public function testOnReceiveShouldCallReceiveCallBack()
    {
        $receiveInvoked = false;
        $receivePromise = null;
        $receiveTimeout = null;
        $receivecb = function ($promise, $timout) use (&$receiveInvoked, &$receivePromise, &$receiveTimeout) {
            $receiveInvoked = true;
            $receivePromise = $promise;
            $receiveTimeout = $timout;
        };

        $promise = new Promise($receivecb, function () {}, function () {});
        $promise->receive();

        $this->assertTrue($receiveInvoked);
        $this->assertInstanceOf(Promise::class, $receivePromise);
        $this->assertNull($receiveTimeout);
    }

    public function testOnReceiveShouldCallReceiveCallBackWithTimeout()
    {
        $receiveInvoked = false;
        $receivePromise = null;
        $receiveTimeout = null;
        $receivecb = function ($promise, $timout) use (&$receiveInvoked, &$receivePromise, &$receiveTimeout) {
            $receiveInvoked = true;
            $receivePromise = $promise;
            $receiveTimeout = $timout;
        };

        $promise = new Promise($receivecb, function () {}, function () {});
        $promise->receive(12345);

        $this->assertTrue($receiveInvoked);
        $this->assertInstanceOf(Promise::class, $receivePromise);
        $this->assertSame(12345, $receiveTimeout);
    }

    public function testOnReceiveNoWaitShouldCallReceiveNoWaitCallBack()
    {
        $receiveInvoked = false;
        $receivecb = function () use (&$receiveInvoked) {
            $receiveInvoked = true;
        };

        $promise = new Promise(function () {}, $receivecb, function () {});
        $promise->receiveNoWait();

        $this->assertTrue($receiveInvoked);
    }

    public function testOnReceiveShouldCallFinallyCallback()
    {
        $invoked = false;
        $cb = function () use (&$invoked) {
            $invoked = true;
        };

        $promise = new Promise(function () {}, function () {}, $cb);
        $promise->receive();

        $this->assertTrue($invoked);
    }

    public function testOnReceiveShouldCallFinallyCallbackEvenIfExceptionThrown()
    {
        $invokedFinally = false;
        $finallycb = function () use (&$invokedFinally) {
            $invokedFinally = true;
        };

        $invokedReceive = false;
        $receivecb = function () use (&$invokedReceive) {
            $invokedReceive = true;
            throw new \Exception();
        };

        try {
            $promise = new Promise($receivecb, function () {}, $finallycb);
            $promise->receive();
        } catch (\Exception $e) {
        }

        $this->assertTrue($invokedReceive);
        $this->assertTrue($invokedFinally);
    }

    public function testOnReceiveShouldThrowExceptionIfCallbackReturnNotMessageInstance()
    {
        $receivecb = function () {
            return new \stdClass();
        };

        $promise = new Promise($receivecb, function () {}, function () {});

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected "Interop\Queue\PsrMessage" but got: "stdClass"');

        $promise->receive();
    }

    public function testOnReceiveNoWaitShouldThrowExceptionIfCallbackReturnNotMessageInstance()
    {
        $receivecb = function () {
            return new \stdClass();
        };

        $promise = new Promise(function () {}, $receivecb, function () {});

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected "Interop\Queue\PsrMessage" but got: "stdClass"');

        $promise->receiveNoWait();
    }

    public function testOnReceiveNoWaitShouldCallFinallyCallbackOnlyIfMessageReceived()
    {
        $invokedReceive = false;
        $receivecb = function () use (&$invokedReceive) {
            $invokedReceive = true;
        };

        $invokedFinally = false;
        $finallycb = function () use (&$invokedFinally) {
            $invokedFinally = true;
        };

        $promise = new Promise(function () {}, $receivecb, $finallycb);
        $promise->receiveNoWait();

        $this->assertTrue($invokedReceive);
        $this->assertFalse($invokedFinally);

        // now should call finally too

        $invokedReceive = false;
        $receivecb = function () use (&$invokedReceive) {
            $invokedReceive = true;

            return new NullMessage();
        };

        $promise = new Promise(function () {}, $receivecb, $finallycb);
        $promise->receiveNoWait();

        $this->assertTrue($invokedReceive);
        $this->assertTrue($invokedFinally);
    }

    public function testOnReceiveShouldNotCallCallbackIfMessageReceivedByReceiveNoWaitBefore()
    {
        $message = new NullMessage();

        $invokedReceive = false;
        $receivecb = function () use (&$invokedReceive) {
            $invokedReceive = true;
        };

        $invokedReceiveNoWait = false;
        $receiveNoWaitCb = function () use (&$invokedReceiveNoWait, $message) {
            $invokedReceiveNoWait = true;

            return $message;
        };

        $promise = new Promise($receivecb, $receiveNoWaitCb, function () {});

        $this->assertSame($message, $promise->receiveNoWait());
        $this->assertTrue($invokedReceiveNoWait);
        $this->assertFalse($invokedReceive);

        // receive should return message but not call callback
        $invokedReceiveNoWait = false;

        $this->assertSame($message, $promise->receive());
        $this->assertFalse($invokedReceiveNoWait);
        $this->assertFalse($invokedReceive);
    }

    public function testOnReceiveNoWaitShouldNotCallCallbackIfMessageReceivedByReceiveBefore()
    {
        $message = new NullMessage();

        $invokedReceive = false;
        $receivecb = function () use (&$invokedReceive, $message) {
            $invokedReceive = true;

            return $message;
        };

        $invokedReceiveNoWait = false;
        $receiveNoWaitCb = function () use (&$invokedReceiveNoWait) {
            $invokedReceiveNoWait = true;
        };

        $promise = new Promise($receivecb, $receiveNoWaitCb, function () {});

        $this->assertSame($message, $promise->receive());
        $this->assertTrue($invokedReceive);
        $this->assertFalse($invokedReceiveNoWait);

        // receiveNoWait should return message but not call callback
        $invokedReceive = false;

        $this->assertSame($message, $promise->receiveNoWait());
        $this->assertFalse($invokedReceiveNoWait);
        $this->assertFalse($invokedReceive);
    }
}
