<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\ConnectionFactory;

class FsConnectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, FsConnectionFactory::class);
    }

    public function testShouldCreateContext()
    {
        $factory = new FsConnectionFactory([
            'path' => __DIR__,
            'pre_fetch_count' => 123,
            'chmod' => 0765,
        ]);

        $context = $factory->createContext();

        $this->assertInstanceOf(FsContext::class, $context);

        $this->assertAttributeSame(__DIR__, 'storeDir', $context);
        $this->assertAttributeSame(123, 'preFetchCount', $context);
        $this->assertAttributeSame(0765, 'chmod', $context);
    }
}
