<?php
namespace Enqueue\JobQueue\Tests\Functional;

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
    protected $container;

    protected function setUp()
    {
        parent::setUp();

        static::$class = null;

        $this->client = static::createClient();
        $this->container = static::$kernel->getContainer();

        $this->startTransaction();
    }

    protected function tearDown()
    {
        $this->rollbackTransaction();

        parent::tearDown();
    }

    /**
     * @return string
     */
    public static function getKernelClass()
    {
        require_once __DIR__.'/app/AppKernel.php';

        return 'AppKernel';
    }

    protected function startTransaction()
    {
        /** @var $em \Doctrine\ORM\EntityManager */
        foreach ($this->container->get('doctrine')->getManagers() as $em) {
            $em->clear();
            $em->getConnection()->beginTransaction();
        }
    }

    protected function rollbackTransaction()
    {
        //the error can be thrown during setUp
        //It would be caught by phpunit and tearDown called.
        //In this case we could not rollback since container may not exist.
        if (false == $this->container) {
            return;
        }

        /** @var $em \Doctrine\ORM\EntityManager */
        foreach ($this->container->get('doctrine')->getManagers() as $em) {
            $connection = $em->getConnection();

            while ($connection->isTransactionActive()) {
                $connection->rollback();
            }
        }
    }
}
