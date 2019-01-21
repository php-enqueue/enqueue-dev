<?php

namespace Enqueue\Doctrine;

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
    private $fallbackFactory;

    public function __construct(DriverFactoryInterface $fallbackFactory)
    {
        $this->fallbackFactory = $fallbackFactory;
    }

    public function create(ConnectionFactory $factory, Config $config, RouteCollection $collection): DriverInterface
    {
        $dsn = $config->getTransportOption('dsn');

        if (empty($dsn)) {
            throw new \LogicException('This driver factory relies on dsn option from transport config. The option is empty or not set.');
        }

        $dsn = Dsn::parseFirst($dsn);

        if ('doctrine' === $dsn->getScheme()) {
            return new DbalDriver($factory->createContext(), $config, $collection);
        }

        return $this->fallbackFactory->create($factory, $config, $collection);
    }
}
