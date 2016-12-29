<?php
namespace Enqueue\Tests\Symfony\Client\Mock;

use Enqueue\Client\Config;
use Enqueue\Client\NullDriver;
use Enqueue\Symfony\Client\SetupBrokerExtensionCommandTrait;
use Enqueue\Transport\Null\NullContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupBrokerExtensionCommand extends Command
{
    use SetupBrokerExtensionCommandTrait;

    protected $extension;

    protected function configure()
    {
        parent::configure();

        $this->configureSetupBrokerExtension();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->extension = $this->getSetupBrokerExtension($input, new NullDriver(new NullContext(), Config::create()));
    }

    public function getExtension()
    {
        return $this->extension;
    }
}
