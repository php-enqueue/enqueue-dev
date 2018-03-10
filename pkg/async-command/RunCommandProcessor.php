<?php

namespace Enqueue\AsyncCommand;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class RunCommandProcessor implements PsrProcessor, CommandSubscriberInterface
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
        $command = RunCommand::jsonUnserialize($message->getBody());

        $phpBin = (new PhpExecutableFinder())->find();
        $consoleBin = file_exists($this->projectDir.'/bin/console') ? './bin/console' : './app/console';

        $process = new Process($phpBin.' '.$consoleBin.' '.$command->getCommandLine(), $this->projectDir);

        $process->run();

        $result = new RunCommandResult($process->getExitCode(), $process->getOutput(), $process->getErrorOutput());

        return Result::reply($context->createMessage(json_encode($result)));
    }

    public static function getSubscribedCommand()
    {
        return [
            'processorName' => Commands::RUN_COMMAND,
            'queueName' => Commands::RUN_COMMAND,
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ];
    }
}
