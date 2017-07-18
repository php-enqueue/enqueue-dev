<?php

namespace Enqueue\LaravelQueue\Tests;

use Enqueue\LaravelQueue\Connector;
use Enqueue\LaravelQueue\Queue;
use Enqueue\Null\NullContext;
use Enqueue\Test\ClassExtensionTrait;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Interop\Queue\PsrQueue;
use PHPUnit\Framework\TestCase;

class ConnectorTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectorInterface()
    {
        $this->assertClassImplements(ConnectorInterface::class, Connector::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new Connector();
    }

    public function testShouldReturnQueueOnConnectMethodCall()
    {
        $connector = new Connector();

        $this->assertInstanceOf(Queue::class, $connector->connect(['dsn' => 'null://']));
    }

    public function testShouldSetExpectedOptionsIfNotProvidedOnConnectMethodCall()
    {
        $connector = new Connector();

        $queue = $connector->connect(['dsn' => 'null://']);

        $this->assertInstanceOf(NullContext::class, $queue->getPsrContext());

        $this->assertInstanceOf(PsrQueue::class, $queue->getQueue());
        $this->assertSame('default', $queue->getQueue()->getQueueName());

        $this->assertSame(0, $queue->getTimeToRun());
    }

    public function testShouldSetExpectedCustomOptionsIfProvidedOnConnectMethodCall()
    {
        $connector = new Connector();

        $queue = $connector->connect([
            'dsn' => 'null://',
            'queue' => 'theCustomQueue',
            'time_to_run' => 123,
        ]);

        $this->assertInstanceOf(NullContext::class, $queue->getPsrContext());

        $this->assertInstanceOf(PsrQueue::class, $queue->getQueue());
        $this->assertSame('theCustomQueue', $queue->getQueue()->getQueueName());

        $this->assertSame(123, $queue->getTimeToRun());
    }
}
