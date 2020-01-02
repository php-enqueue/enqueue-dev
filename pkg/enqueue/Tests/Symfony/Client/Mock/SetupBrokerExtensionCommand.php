<?php

namespace Enqueue\Tests\Symfony\Client\Mock;

use Enqueue\Client\Config;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\RouteCollection;
use Enqueue\Null\NullContext;
use Enqueue\Symfony\Client\SetupBrokerExtensionCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupBrokerExtensionCommand extends Command
{
    use SetupBrokerExtensionCommandTrait;

    protected $extension;

    public function getExtension()
    {
        return $this->extension;
    }

    protected function configure()
    {
        parent::configure();

        $this->configureSetupBrokerExtension();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->extension = $this->getSetupBrokerExtension($input, new GenericDriver(
            new NullContext(),
            Config::create(),
            new RouteCollection([])
        ));

        return 0;
    }
}
