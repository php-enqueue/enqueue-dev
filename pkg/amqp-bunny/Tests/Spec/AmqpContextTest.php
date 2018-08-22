<?php

namespace Enqueue\AmqpBunny\Tests\Spec;

use Bunny\Channel;
use Enqueue\AmqpBunny\AmqpContext;
use Interop\Queue\Spec\PsrContextSpec;

class AmqpContextTest extends PsrContextSpec
{
    protected function createContext()
    {
        $channel = $this->createMock(Channel::class);

        return new AmqpContext($channel, []);
    }
}
