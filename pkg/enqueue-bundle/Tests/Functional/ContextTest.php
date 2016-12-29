<?php
namespace Enqueue\EnqueueBundle\Tests\Functional;

use Enqueue\Psr\Context;

/**
 * @group functional
 */
class ContextTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->container->get('enqueue.transport.context');

        $this->assertInstanceOf(Context::class, $connection);
    }
}
