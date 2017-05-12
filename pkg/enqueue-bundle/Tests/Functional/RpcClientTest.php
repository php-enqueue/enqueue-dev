<?php
namespace Enqueue\Bundle\Tests\Functional;

/**
 * @group functional
 */
class RpcClientTest extends WebTestCase
{
    public function testTransportRpcClientCouldBeGetFromContainerAsService()
    {
        $connection = $this->container->get('enqueue.transport.rpc_client');

        $this->assertInstanceOf(\Enqueue\Rpc\RpcClient::class, $connection);
    }

    public function testClientRpcClientCouldBeGetFromContainerAsService()
    {
        $connection = $this->container->get('enqueue.client.rpc_client');

        $this->assertInstanceOf(\Enqueue\Client\RpcClient::class, $connection);
    }
}
