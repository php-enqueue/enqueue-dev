<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Rpc\RpcClient;

/**
 * @group functional
 */
class RpcClientTest extends WebTestCase
{
    public function testTransportRpcClientCouldBeGetFromContainerAsService()
    {
        $connection = $this->container->get('enqueue.transport.rpc_client');

        $this->assertInstanceOf(RpcClient::class, $connection);
    }
}
