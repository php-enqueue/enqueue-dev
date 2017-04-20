<?php

namespace Enqueue\Tests\Client\Meta;

use Enqueue\Client\Meta\QueueMeta;
use PHPUnit\Framework\TestCase;

class QueueMetaTest extends TestCase
{
    public function testCouldBeConstructedWithExpectedArguments()
    {
        $destination = new QueueMeta('aClientName', 'aTransportName');

        $this->assertAttributeEquals('aClientName', 'clientName', $destination);
        $this->assertAttributeEquals('aTransportName', 'transportName', $destination);
        $this->assertAttributeEquals([], 'processors', $destination);
    }

    public function testShouldAllowGetClientNameSetInConstructor()
    {
        $destination = new QueueMeta('theClientName', 'aTransportName');

        $this->assertSame('theClientName', $destination->getClientName());
    }

    public function testShouldAllowGetTransportNameSetInConstructor()
    {
        $destination = new QueueMeta('aClientName', 'theTransportName');

        $this->assertSame('theTransportName', $destination->getTransportName());
    }

    public function testShouldAllowGetSubscribersSetInConstructor()
    {
        $destination = new QueueMeta('aClientName', 'aTransportName', ['aSubscriber']);

        $this->assertSame(['aSubscriber'], $destination->getProcessors());
    }
}
