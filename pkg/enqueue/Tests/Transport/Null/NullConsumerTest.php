<?php

namespace Enqueue\Tests\Transport\Null;

use Enqueue\Psr\Consumer;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullConsumer;
use Enqueue\Transport\Null\NullMessage;
use Enqueue\Transport\Null\NullQueue;

class NullConsumerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, NullConsumer::class);
    }

    public function testCouldBeConstructedWithQueueAsArgument()
    {
        new NullConsumer(new NullQueue('aName'));
    }

    public function testShouldAlwaysReturnNullOnReceive()
    {
        $consumer = new NullConsumer(new NullQueue('theQueueName'));

        $this->assertNull($consumer->receive());
        $this->assertNull($consumer->receive());
        $this->assertNull($consumer->receive());
    }

    public function testShouldAlwaysReturnNullOnReceiveNoWait()
    {
        $consumer = new NullConsumer(new NullQueue('theQueueName'));

        $this->assertNull($consumer->receiveNoWait());
        $this->assertNull($consumer->receiveNoWait());
        $this->assertNull($consumer->receiveNoWait());
    }

    public function testShouldDoNothingOnAcknowledge()
    {
        $consumer = new NullConsumer(new NullQueue('theQueueName'));

        $consumer->acknowledge(new NullMessage());
    }

    public function testShouldDoNothingOnReject()
    {
        $consumer = new NullConsumer(new NullQueue('theQueueName'));

        $consumer->reject(new NullMessage());
    }
}
