<?php

namespace Enqueue\Bundle\DoctrineSchema;

use Enqueue\Client\Config;
use Enqueue\Client\Driver\DbalDriver;
use Enqueue\Client\DriverFactoryInterface;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\RouteCollection;
use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;

class DoctrineDriverFactory implements DriverFactoryInterface
{
    /**
     * @var DriverFactoryInterface
     */
    private $parentFactory;

    public function __construct(DriverFactoryInterface $parentFactory)
    {
        $this->parentFactory = $parentFactory;
    }

    public function create(ConnectionFactory $factory, Config $config, RouteCollection $collection): DriverInterface
    {
        $dsn = $config->getTransportOption('dsn');

        if (empty($dsn)) {
            throw new \LogicException('This driver factory relies on dsn option from transport config. The option is empty or not set.');
        }

        $dsn = Dsn::parseFirst($dsn);

        if ($dsn->getScheme() === 'doctrine') {
            return new DbalDriver($factory->createContext(), $config, $collection);
        }

        return $this->parentFactory->create($factory, $config, $collection);
    }
}
