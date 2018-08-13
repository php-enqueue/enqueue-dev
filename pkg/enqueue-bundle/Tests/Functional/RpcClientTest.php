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
        $connection = static::$container->get(RpcClient::class);

        $this->assertInstanceOf(RpcClient::class, $connection);
    }
}
