<?php

namespace Enqueue\Symfony\Processor;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RunCommandProcessor implements PsrProcessor, CommandSubscriberInterface
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct($projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $commandline = $message->getBody();

        $process = new Process('./bin/console '.$commandline, $this->projectDir);

        try {
            $process->mustRun();

            return Result::ACK;
        } catch (ProcessFailedException $e) {
            return Result::reject(sprintf('The process failed with exception: "%s" in %s at %s', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    public static function getSubscribedCommand()
    {
        return 'run_command';
    }
}
