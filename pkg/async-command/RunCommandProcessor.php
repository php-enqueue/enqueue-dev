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

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function process(PsrMessage $message, PsrContext $context): Result
    {
        $command = RunCommand::jsonUnserialize($message->getBody());

        $phpBin = (new PhpExecutableFinder())->find();
        $consoleBin = file_exists($this->projectDir.'/bin/console') ? './bin/console' : './app/console';

        $process = new Process($phpBin.' '.$consoleBin.' '.$this->getCommandLine($command), $this->projectDir);

        $process->run();

        if ($message->getReplyTo()) {
            $result = new CommandResult($process->getExitCode(), $process->getOutput(), $process->getErrorOutput());

            return Result::reply($context->createMessage(json_encode($result)));
        }

        return Result::ack();
    }

    public static function getSubscribedCommand(): array
    {
        return [
            'processorName' => Commands::RUN_COMMAND,
            'queueName' => Commands::RUN_COMMAND,
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ];
    }

    /**
     * @return string
     */
    private function getCommandLine(RunCommand $command): string
    {
        $optionsString = '';
        foreach ($command->getOptions() as $name => $value) {
            $optionsString .= " $name=$value";
        }
        $optionsString = trim($optionsString);

        $argumentsString = '';
        foreach ($command->getArguments() as $value) {
            $argumentsString .= " $value";
        }
        $argumentsString = trim($argumentsString);

        return trim($command->getCommand().' '.$argumentsString.' '.$optionsString);
    }
}
