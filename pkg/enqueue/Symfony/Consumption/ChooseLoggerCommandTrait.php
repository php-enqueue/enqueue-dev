<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\Extension\LoggerExtension;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

trait ChooseLoggerCommandTrait
{
    protected function configureLoggerExtension(): void
    {
        $this
            ->addOption('logger', null, InputOption::VALUE_OPTIONAL, 'A logger to be used. Could be "default", "null", "stdout".', 'default')
        ;
    }

    protected function getLoggerExtension(InputInterface $input, OutputInterface $output): ?LoggerExtension
    {
        $logger = $input->getOption('logger');
        switch ($logger) {
            case 'null':
                return new LoggerExtension(new NullLogger());
            case 'stdout':
                return new LoggerExtension(new ConsoleLogger($output));
            case 'default':
                return null;
            default:
                throw new \LogicException(sprintf('The logger "%s" is not supported', $logger));
        }
    }
}
