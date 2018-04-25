<?php

namespace Enqueue\Stomp\Tests\Client;

use Enqueue\Stomp\Client\ManagementClient;
use RabbitMq\ManagementApi\Api\Binding;
use RabbitMq\ManagementApi\Api\Exchange;
use RabbitMq\ManagementApi\Api\Queue;
use RabbitMq\ManagementApi\Client;

class ManagementClientTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldDeclareExchange()
    {
        $exchange = $this->createExchangeMock();
        $exchange
            ->expects($this->once())
            ->method('create')
            ->with('vhost', 'name', ['options'])
        ;

        $client = $this->createClientMock();
        $client
            ->expects($this->once())
            ->method('exchanges')
            ->willReturn($exchange)
        ;

        $management = new ManagementClient($client, 'vhost');
        $management->declareExchange('name', ['options']);
    }

    public function testCouldDeclareQueue()
    {
        $queue = $this->createQueueMock();
        $queue
            ->expects($this->once())
            ->method('create')
            ->with('vhost', 'name', ['options'])
        ;

        $client = $this->createClientMock();
        $client
            ->expects($this->once())
            ->method('queues')
            ->willReturn($queue)
        ;

        $management = new ManagementClient($client, 'vhost');
        $management->declareQueue('name', ['options']);
    }

    public function testCouldBind()
    {
        $binding = $this->createBindingMock();
        $binding
            ->expects($this->once())
            ->method('create')
            ->with('vhost', 'exchange', 'queue', 'routing-key', ['arguments'])
        ;

        $client = $this->createClientMock();
        $client
            ->expects($this->once())
            ->method('bindings')
            ->willReturn($binding)
        ;

        $management = new ManagementClient($client, 'vhost');
        $management->bind('exchange', 'queue', 'routing-key', ['arguments']);
    }

    public function testCouldCreateNewInstanceUsingFactory()
    {
        $instance = ManagementClient::create('', '');

        $this->assertInstanceOf(ManagementClient::class, $instance);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Client
     */
    private function createClientMock()
    {
        return $this->createMock(Client::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Exchange
     */
    private function createExchangeMock()
    {
        return $this->createMock(Exchange::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Queue
     */
    private function createQueueMock()
    {
        return $this->createMock(Queue::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Binding
     */
    private function createBindingMock()
    {
        return $this->createMock(Binding::class);
    }
}
