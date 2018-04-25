<?php

namespace Enqueue\Tests\Client\Meta;

use Enqueue\Client\Meta\QueueMeta;
use PHPUnit\Framework\TestCase;

class QueueMetaTest extends TestCase
{
    public function testCouldBeConstructedWithExpectedArguments()
    {
        $meta = new QueueMeta('aClientName', 'aTransportName');

        $this->assertAttributeEquals('aClientName', 'clientName', $meta);
        $this->assertAttributeEquals('aTransportName', 'transportName', $meta);
        $this->assertAttributeEquals([], 'processors', $meta);
    }

    public function testShouldAllowGetClientNameSetInConstructor()
    {
        $meta = new QueueMeta('theClientName', 'aTransportName');

        $this->assertSame('theClientName', $meta->getClientName());
    }

    public function testShouldAllowGetTransportNameSetInConstructor()
    {
        $meta = new QueueMeta('aClientName', 'theTransportName');

        $this->assertSame('theTransportName', $meta->getTransportName());
    }

    public function testShouldAllowGetSubscribersSetInConstructor()
    {
        $meta = new QueueMeta('aClientName', 'aTransportName', ['aSubscriber']);

        $this->assertSame(['aSubscriber'], $meta->getProcessors());
    }
}
