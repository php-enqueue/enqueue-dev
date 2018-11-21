<?php

namespace Enqueue\AzureStorage\Tests;

use Enqueue\AzureStorage\AzureStorageConnectionFactory;
use Enqueue\AzureStorage\AzureStorageContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class AzureStorageConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, AzureStorageConnectionFactory::class);
    }

}
