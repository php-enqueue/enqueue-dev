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
        $rpcClient = static::$container->get('test_enqueue.transport.default.rpc_client');

        $this->assertInstanceOf(RpcClient::class, $rpcClient);
    }
}
