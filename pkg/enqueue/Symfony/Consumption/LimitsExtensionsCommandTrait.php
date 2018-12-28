<?php

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\NicenessExtension;
use Enqueue\Consumption\ExtensionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

trait LimitsExtensionsCommandTrait
{
    protected function configureLimitsExtensions()
    {
        $this
            ->addOption('message-limit', null, InputOption::VALUE_REQUIRED, 'Consume n messages and exit')
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Consume messages during this time')
            ->addOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Consume messages until process reaches this memory limit in MB')
            ->addOption('niceness', null, InputOption::VALUE_REQUIRED, 'Set process niceness');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     *
     * @return ExtensionInterface[]
     */
    protected function getLimitsExtensions(InputInterface $input, OutputInterface $output)
    {
        $extensions = [];

        $messageLimit = (int) $input->getOption('message-limit');
        if ($messageLimit) {
            $extensions[] = new LimitConsumedMessagesExtension($messageLimit);
        }

        $timeLimit = $input->getOption('time-limit');
        if ($timeLimit) {
            try {
                $timeLimit = new \DateTime($timeLimit);
            } catch (\Exception $e) {
                $output->writeln('<error>Invalid time limit</error>');

                throw $e;
            }

            $extensions[] = new LimitConsumptionTimeExtension($timeLimit);
        }

        $memoryLimit = (int) $input->getOption('memory-limit');
        if ($memoryLimit) {
            $extensions[] = new LimitConsumerMemoryExtension($memoryLimit);
        }

        $niceness = $input->getOption('niceness');
        if (!empty($niceness) && is_numeric($niceness)) {
            $extensions[] = new NicenessExtension((int) $niceness);
        }

        return $extensions;
    }
}
