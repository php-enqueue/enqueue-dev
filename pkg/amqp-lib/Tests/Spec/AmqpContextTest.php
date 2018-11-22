<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpContext;
use Interop\Queue\Spec\ContextSpec;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

class AmqpContextTest extends ContextSpec
{
    protected function createContext()
    {
        $channel = $this->createMock(AMQPChannel::class);

        $con = $this->createMock(AbstractConnection::class);
        $con
            ->expects($this->any())
            ->method('channel')
            ->willReturn($channel)
        ;

        return new AmqpContext($con, []);
    }
}
