<?php

namespace Enqueue\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Enqueue\ConnectionFactoryFactoryInterface;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;

class DoctrineConnectionFactoryFactory implements ConnectionFactoryFactoryInterface
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var ConnectionFactoryFactoryInterface
     */
    private $fallbackFactory;

    public function __construct(ManagerRegistry $doctrine, ConnectionFactoryFactoryInterface $fallbackFactory)
    {
        $this->doctrine = $doctrine;
        $this->fallbackFactory = $fallbackFactory;
    }

    public function create($config): ConnectionFactory
    {
        if (is_string($config)) {
            $config = ['dsn' => $config];
        }

        if (false == is_array($config)) {
            throw new \InvalidArgumentException('The config must be either array or DSN string.');
        }

        if (false == array_key_exists('dsn', $config)) {
            throw new \InvalidArgumentException('The config must have dsn key set.');
        }

        $dsn = Dsn::parseFirst($config['dsn']);

        if ('doctrine' === $dsn->getScheme()) {
            $config = $dsn->getQuery();
            $config['connection_name'] = $dsn->getHost();

            return new ManagerRegistryConnectionFactory($this->doctrine, $config);
        }

        return $this->fallbackFactory->create($config);
    }
}
