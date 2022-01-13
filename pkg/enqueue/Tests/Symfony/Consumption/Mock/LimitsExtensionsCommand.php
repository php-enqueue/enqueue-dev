<?php

namespace Enqueue\Tests\Symfony\Consumption\Mock;

use Enqueue\Symfony\Consumption\LimitsExtensionsCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LimitsExtensionsCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    protected $extensions;

    public function getExtensions()
    {
        return $this->extensions;
    }

    protected function configure()
    {
        parent::configure();

        $this->configureLimitsExtensions();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->extensions = $this->getLimitsExtensions($input, $output);

        return 0;
    }
}
