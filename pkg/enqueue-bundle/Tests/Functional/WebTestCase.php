<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Bundle\Tests\Functional\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ContainerInterface
     */
    protected static $container;

    protected function setUp()
    {
        parent::setUp();

        static::$class = null;

        $this->client = static::createClient();

        if (false == static::$container) {
            static::$container = static::$kernel->getContainer();
        }
    }

    /**
     * @return string
     */
    public static function getKernelClass()
    {
        include_once __DIR__.'/App/AppKernel.php';

        return AppKernel::class;
    }
}
