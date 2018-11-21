<?php

namespace Enqueue\AzureStorage\Tests;

use Enqueue\AzureStorage\AzureStorageDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Test\DestinationBasicTestCase;
use Interop\Queue\Queue;
use Interop\Queue\Topic;

class AzureStorageDestinationTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsTopicAndQueueInterfaces()
    {
        $this->assertClassImplements(Topic::class, AzureStorageDestination::class);
        $this->assertClassImplements(Queue::class, AzureStorageDestination::class);
    }

    public function testShouldReturnNameSetInConstructor()
    {
        $destination = new AzureStorageDestination('aDestinationName');

        $this->assertSame('aDestinationName', $destination->getName());
        $this->assertSame('aDestinationName', $destination->getQueueName());
        $this->assertSame('aDestinationName', $destination->getTopicName());
    }
}
