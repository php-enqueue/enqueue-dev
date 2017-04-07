<?php

namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Bundle\Tests\Functional\WebTestCase;
use Enqueue\Client\ProducerInterface;

/**
 * @group functional
 */
class ProducerTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $messageProducer = $this->container->get('enqueue.client.producer');

        $this->assertInstanceOf(ProducerInterface::class, $messageProducer);
    }

    public function testCouldBeGetFromContainerAsShortenAlias()
    {
        $messageProducer = $this->container->get('enqueue.client.producer');
        $aliasMessageProducer = $this->container->get('enqueue.message_producer');

        $this->assertSame($messageProducer, $aliasMessageProducer);
    }
}
