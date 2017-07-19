<?php

namespace Enqueue\LaravelQueue\Tests;

use Enqueue\LaravelQueue\Connector;
use Enqueue\LaravelQueue\Queue;
use Enqueue\Null\NullConnectionFactory;
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

    public function testThrowIfConnectorFactoryClassOptionNotSet()
    {
        $connector = new Connector();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "connection_factory_class" option is required');
        $connector->connect([]);
    }

    public function testThrowIfConnectorFactoryClassOptionIsNotValidClass()
    {
        $connector = new Connector();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "connection_factory_class" option "invalidClass" is not a class');
        $connector->connect([
            'connection_factory_class' => 'invalidClass',
        ]);
    }

    public function testThrowIfConnectorFactoryClassOptionDoesNotImplementPsrConnectionFactoryInterface()
    {
        $connector = new Connector();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "connection_factory_class" option must contain a class that implements "Interop\Queue\PsrConnectionFactory" but it is not');
        $connector->connect([
            'connection_factory_class' => \stdClass::class,
        ]);
    }

    public function testShouldReturnQueueOnConnectMethodCall()
    {
        $connector = new Connector();

        $this->assertInstanceOf(Queue::class, $connector->connect([
            'connection_factory_class' => NullConnectionFactory::class,
        ]));
    }

    public function testShouldSetExpectedOptionsIfNotProvidedOnConnectMethodCall()
    {
        $connector = new Connector();

        $queue = $connector->connect(['connection_factory_class' => NullConnectionFactory::class]);

        $this->assertInstanceOf(NullContext::class, $queue->getPsrContext());

        $this->assertInstanceOf(PsrQueue::class, $queue->getQueue());
        $this->assertSame('default', $queue->getQueue()->getQueueName());

        $this->assertSame(0, $queue->getTimeToRun());
    }

    public function testShouldSetExpectedCustomOptionsIfProvidedOnConnectMethodCall()
    {
        $connector = new Connector();

        $queue = $connector->connect([
            'connection_factory_class' => NullConnectionFactory::class,
            'queue' => 'theCustomQueue',
            'time_to_run' => 123,
        ]);

        $this->assertInstanceOf(NullContext::class, $queue->getPsrContext());

        $this->assertInstanceOf(PsrQueue::class, $queue->getQueue());
        $this->assertSame('theCustomQueue', $queue->getQueue()->getQueueName());

        $this->assertSame(123, $queue->getTimeToRun());
    }
}
