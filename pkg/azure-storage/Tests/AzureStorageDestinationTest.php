<?php

namespace Enqueue\Redis\Tests;

use Enqueue\AzureStorage\AzureStorageDestination;
use Interop\Queue\Test\DestinationBasicTestCase;

class AzureStorageDestinationTest extends DestinationBasicTestCase
{
    public function getDestination()
    {
        return new AzureStorageDestination();
    }
}
