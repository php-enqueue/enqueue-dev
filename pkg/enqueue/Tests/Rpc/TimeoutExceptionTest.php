<?php

namespace Enqueue\Tests\Rpc;

use Enqueue\Rpc\TimeoutException;
use PHPUnit\Framework\TestCase;

class TimeoutExceptionTest extends TestCase
{
    public function testShouldBeSubClassOfLogicException()
    {
        $rc = new \ReflectionClass(TimeoutException::class);

        $this->assertTrue($rc->isSubclassOf(\LogicException::class));
    }

    public function testShouldCreateSelfInstanceWithPreSetMessage()
    {
        $exception = TimeoutException::create('theTimeout', 'theCorrelationId');

        $this->assertInstanceOf(TimeoutException::class, $exception);
        $this->assertEquals('Rpc call timeout is reached without receiving a reply message. Timeout: theTimeout, CorrelationId: theCorrelationId', $exception->getMessage());
    }
}
