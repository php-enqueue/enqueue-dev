<?php

namespace Enqueue\AmqpBunny\Tests\Spec;

use Bunny\Channel;
use Enqueue\AmqpBunny\AmqpContext;
use Interop\Queue\Spec\ContextSpec;

class AmqpContextTest extends ContextSpec
{
    protected function createContext()
    {
        $channel = $this->createMock(Channel::class);

        return new AmqpContext($channel, []);
    }
}
