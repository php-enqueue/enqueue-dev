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
        $connection = $this->container->get(QueueMetaRegistry::class);

        $this->assertInstanceOf(QueueMetaRegistry::class, $connection);
    }
}
