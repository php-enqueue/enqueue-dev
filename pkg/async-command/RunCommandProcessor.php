<?php

namespace Enqueue\AsyncCommand;

use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class RunCommandProcessor implements Processor
{
    /**
     * @var int
     */
    private $timeout;

    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir, int $timeout = 60)
    {
        $this->projectDir = $projectDir;
        $this->timeout = $timeout;
    }

    public function process(Message $message, Context $context): Result
    {
        $command = RunCommand::jsonUnserialize($message->getBody());

        $phpBin = (new PhpExecutableFinder())->find();
        $consoleBin = file_exists($this->projectDir.'/bin/console') ? './bin/console' : './app/console';

        $process = new Process(array_merge(
            [$phpBin, $consoleBin, $command->getCommand()],
            $command->getArguments(),
            $this->getCommandLineOptions($command)
        ), $this->projectDir);
        $process->setTimeout($this->timeout);
        $process->run();

        if ($message->getReplyTo()) {
            $result = new CommandResult($process->getExitCode(), $process->getOutput(), $process->getErrorOutput());

            return Result::reply($context->createMessage(json_encode($result)));
        }

        return Result::ack();
    }

    /**
     * @return string[]
     */
    private function getCommandLineOptions(RunCommand $command): array
    {
        $options = [];
        foreach ($command->getOptions() as $name => $value) {
            $options[] = "$name=$value";
        }

        return $options;
    }
}
