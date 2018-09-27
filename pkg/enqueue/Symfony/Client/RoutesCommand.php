<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\Config;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RoutesCommand extends Command
{
    protected static $defaultName = 'enqueue:routes';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var RouteCollection
     */
    private $routeCollection;

    public function __construct(Config $config, RouteCollection $routeCollection)
    {
        parent::__construct(static::$defaultName);

        $this->config = $config;
        $this->routeCollection = $routeCollection;
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['debug:enqueue:routes'])
            ->setDescription('A command lists all registered routes.')
            ->addOption('show-route-options', null, InputOption::VALUE_NONE, 'Adds ability to hide options.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $routes = $this->routeCollection->all();
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

            foreach ($this->routeCollection->all() as $route) {
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

        return null;
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
        $queue = $route->getQueue() ?: $this->config->getDefaultProcessorQueueName();

        return $route->isPrefixQueue() ? $queue.' (prefixed)' : $queue.' (as is)';
    }

    private function formatOptions(Route $route): string
    {
        return var_export($route->getOptions(), true);
    }
}
