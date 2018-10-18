<?php

namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Client\SpoolProducer;

/**
 * @group functional
 */
class SpoolProducerTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $producer = static::$container->get('test_enqueue.client.default.spool_producer');

        $this->assertInstanceOf(SpoolProducer::class, $producer);
    }
}
