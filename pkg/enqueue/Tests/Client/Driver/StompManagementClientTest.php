<?php

namespace Enqueue\Tests\Client\Driver;

use Enqueue\Client\Driver\StompManagementClient;
use RabbitMq\ManagementApi\Api\Binding;
use RabbitMq\ManagementApi\Api\Exchange;
use RabbitMq\ManagementApi\Api\Queue;
use RabbitMq\ManagementApi\Client;

class StompManagementClientTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldDeclareExchange()
    {
        $exchange = $this->createExchangeMock();
        $exchange
            ->expects($this->once())
            ->method('create')
            ->with('vhost', 'name', ['options'])
            ->willReturn([])
        ;

        $client = $this->createClientMock();
        $client
            ->expects($this->once())
            ->method('exchanges')
            ->willReturn($exchange)
        ;

        $management = new StompManagementClient($client, 'vhost');
        $management->declareExchange('name', ['options']);
    }

    public function testCouldDeclareQueue()
    {
        $queue = $this->createQueueMock();
        $queue
            ->expects($this->once())
            ->method('create')
            ->with('vhost', 'name', ['options'])
            ->willReturn([])
        ;

        $client = $this->createClientMock();
        $client
            ->expects($this->once())
            ->method('queues')
            ->willReturn($queue)
        ;

        $management = new StompManagementClient($client, 'vhost');
        $management->declareQueue('name', ['options']);
    }

    public function testCouldBind()
    {
        $binding = $this->createBindingMock();
        $binding
            ->expects($this->once())
            ->method('create')
            ->with('vhost', 'exchange', 'queue', 'routing-key', ['arguments'])
            ->willReturn([])
        ;

        $client = $this->createClientMock();
        $client
            ->expects($this->once())
            ->method('bindings')
            ->willReturn($binding)
        ;

        $management = new StompManagementClient($client, 'vhost');
        $management->bind('exchange', 'queue', 'routing-key', ['arguments']);
    }

    public function testCouldCreateNewInstanceUsingFactory()
    {
        $instance = StompManagementClient::create('', '');

        $this->assertInstanceOf(StompManagementClient::class, $instance);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Client
     */
    private function createClientMock()
    {
        return $this->createMock(Client::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Exchange
     */
    private function createExchangeMock()
    {
        return $this->createMock(Exchange::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Queue
     */
    private function createQueueMock()
    {
        return $this->createMock(Queue::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Binding
     */
    private function createBindingMock()
    {
        return $this->createMock(Binding::class);
    }
}
