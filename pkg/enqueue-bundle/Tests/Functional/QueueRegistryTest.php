<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\Meta\QueueMetaRegistry;

/**
 * @group functional
 */
class QueueRegistryTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->container->get('enqueue.client.meta.queue_meta_registry');

        $this->assertInstanceOf(QueueMetaRegistry::class, $connection);
    }
}
