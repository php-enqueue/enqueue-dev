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
        $producer = static::$container->get(SpoolProducer::class);

        $this->assertInstanceOf(SpoolProducer::class, $producer);
    }

    /**
     * @group legacy
     */
    public function testCouldBeGetFromContainerAsShortenAlias()
    {
        $producer = static::$container->get('enqueue.client.spool_producer');
        $aliasProducer = static::$container->get('enqueue.spool_producer');

        $this->assertSame($producer, $aliasProducer);
    }
}
