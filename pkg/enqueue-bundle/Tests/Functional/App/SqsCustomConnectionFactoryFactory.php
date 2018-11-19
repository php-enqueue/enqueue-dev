<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\ConnectionFactoryFactoryInterface;
use Enqueue\Sqs\SqsConnectionFactory;
use Interop\Queue\ConnectionFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SqsCustomConnectionFactoryFactory implements ConnectionFactoryFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create($config): ConnectionFactory
    {
        if (false == isset($config['service'])) {
            throw new \LogicException('The sqs client has to be set');
        }

        return new SqsConnectionFactory($this->container->get($config['service']));
    }
}
