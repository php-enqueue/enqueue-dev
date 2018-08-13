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
        $connection = static::$container->get(TopicMetaRegistry::class);

        $this->assertInstanceOf(TopicMetaRegistry::class, $connection);
    }
}
