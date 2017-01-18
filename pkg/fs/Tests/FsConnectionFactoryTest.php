<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Psr\ConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;

class FsConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, FsConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new FsConnectionFactory([]);

        $this->assertAttributeEquals([
            'store_dir' => null,
            'pre_fetch_count' => 1,
            'chmod' => 0600,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new FsConnectionFactory(['store_dir' => 'theCustomDir']);

        $this->assertAttributeEquals([
            'store_dir' => 'theCustomDir',
            'pre_fetch_count' => 1,
            'chmod' => 0600,
        ], 'config', $factory);
    }

    public function testShouldCreateContext()
    {
        $factory = new FsConnectionFactory([
            'store_dir' => 'theDir',
            'pre_fetch_count' => 123,
            'chmod' => 0765,
        ]);

        $context = $factory->createContext();

        $this->assertInstanceOf(FsContext::class, $context);

        $this->assertAttributeSame('theDir', 'storeDir', $context);
        $this->assertAttributeSame(123, 'preFetchCount', $context);
        $this->assertAttributeSame(0765, 'chmod', $context);
    }
}
