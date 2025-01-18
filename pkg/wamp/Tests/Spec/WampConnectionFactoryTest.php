<?php

namespace Enqueue\Wamp\Tests\Spec;

use Enqueue\Wamp\WampConnectionFactory;
use Interop\Queue\Spec\ConnectionFactorySpec;

/**
 * @group Wamp
 */
class WampConnectionFactoryTest extends ConnectionFactorySpec
{
    protected function createConnectionFactory()
    {
        return new WampConnectionFactory();
    }
}
