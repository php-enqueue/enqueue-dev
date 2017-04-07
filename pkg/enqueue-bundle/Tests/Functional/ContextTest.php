<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Psr\PsrContext;

/**
 * @group functional
 */
class ContextTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->container->get('enqueue.transport.context');

        $this->assertInstanceOf(PsrContext::class, $connection);
    }
}
