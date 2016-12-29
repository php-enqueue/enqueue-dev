<?php
namespace Enqueue\Bundle\Tests\Functional\Client;

use Enqueue\Client\MessageProducerInterface;
use Enqueue\Bundle\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class MessageProducerTest extends WebTestCase
{
    public function testCouldBeGetFromContainerAsService()
    {
        $messageProducer = $this->container->get('enqueue.client.message_producer');

        $this->assertInstanceOf(MessageProducerInterface::class, $messageProducer);
    }

    public function testCouldBeGetFromContainerAsShortenAlias()
    {
        $messageProducer = $this->container->get('enqueue.client.message_producer');
        $aliasMessageProducer = $this->container->get('enqueue.message_producer');

        $this->assertSame($messageProducer, $aliasMessageProducer);
    }
}
