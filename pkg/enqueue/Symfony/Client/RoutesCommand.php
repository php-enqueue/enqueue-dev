<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RoutesCommand extends Command
{
    protected static $defaultName = 'enqueue:routes';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $defaultClient;

    /**
     * @var string
     */
    private $driverIdPatter;

    /**
     * @var DriverInterface
     */
    private $driver;

    public function __construct(ContainerInterface $container, string $defaultClient, string $driverIdPatter = 'enqueue.client.%s.driver')
    {
        $this->container = $container;
        $this->defaultClient = $defaultClient;
        $this->driverIdPatter = $driverIdPatter;

        parent::__construct(static::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['debug:enqueue:routes'])
            ->setDescription('A command lists all registered routes.')
            ->addOption('show-route-options', null, InputOption::VALUE_NONE, 'Adds ability to hide options.')
            ->addOption('client', 'c', InputOption::VALUE_OPTIONAL, 'The client to consume messages from.', $this->defaultClient)
        ;

        $this->driver = null;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->driver = $this->getDriver($input->getOption('client'));
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException(sprintf('Client "%s" is not supported.', $input->getOption('client')), null, $e);
        }

        $routes = $this->driver->getRouteCollection()->all();
        $output->writeln(sprintf('Found %s routes', count($routes)));
        $output->writeln('');

        if ($routes) {
            $table = new Table($output);
            $table->setHeaders(['Type', 'Source', 'Queue', 'Processor', 'Options']);

            $firstRow = true;
            foreach ($routes as $route) {
                if (false == $firstRow) {
                    $table->addRow(new TableSeparator());

                    $firstRow = false;
                }

                if ($route->isCommand()) {
                    continue;
                }

                $table->addRow([
                    $this->formatSourceType($route),
                    $route->getSource(),
                    $this->formatQueue($route),
                    $this->formatProcessor($route),
                    $input->getOption('show-route-options') ? $this->formatOptions($route) : '(hidden)',
                ]);
            }

            foreach ($routes as $route) {
                if ($route->isTopic()) {
                    continue;
                }

                $table->addRow([
                    $this->formatSourceType($route),
                    $route->getSource(),
                    $this->formatQueue($route),
                    $this->formatProcessor($route),
                    $input->getOption('show-route-options') ? $this->formatOptions($route) : '(hidden)',
                ]);
            }

            $table->render();
        }

        return 0;
    }

    private function formatSourceType(Route $route): string
    {
        if ($route->isCommand()) {
            return 'command';
        }

        if ($route->isTopic()) {
            return 'topic';
        }

        return 'unknown';
    }

    private function formatProcessor(Route $route): string
    {
        if ($route->isProcessorExternal()) {
            return 'n\a (external)';
        }

        $processor = $route->getProcessor();

        return $route->isProcessorExclusive() ? $processor.' (exclusive)' : $processor;
    }

    private function formatQueue(Route $route): string
    {
        $queue = $route->getQueue() ?: $this->driver->getConfig()->getDefaultQueue();

        return $route->isPrefixQueue() ? $queue.' (prefixed)' : $queue.' (as is)';
    }

    private function formatOptions(Route $route): string
    {
        return var_export($route->getOptions(), true);
    }

    private function getDriver(string $client): DriverInterface
    {
        return $this->container->get(sprintf($this->driverIdPatter, $client));
    }
}

function enqueue()
{
}
