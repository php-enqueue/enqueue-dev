<?php

namespace Enqueue\Bundle\Tests\Functional;

use Interop\Queue\Context;

/**
 * @group functional
 */
class ContextTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $connection = static::$container->get('test_enqueue.transport.default.context');

        $this->assertInstanceOf(Context::class, $connection);
    }
}
