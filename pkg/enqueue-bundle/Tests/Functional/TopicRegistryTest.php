<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\Meta\TopicMetaRegistry;

/**
 * @group functional
 */
class TopicRegistryTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $connection = $this->container->get('enqueue.client.meta.topic_meta_registry');

        $this->assertInstanceOf(TopicMetaRegistry::class, $connection);
    }
}
