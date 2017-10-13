<?php
namespace Enqueue\Symfony;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class Daemon
{
    /**
     * @var ProcessBuilder[]
     */
    private $builders;

    /**
     * @var int[]
     */
    private $quantities;

    /**
     * @var Process[]
     */
    private $processes;

    /**
     * @var bool
     */
    private $interrupt;

    /**
     * @var bool
     */
    private $quit;

    private $asyncSignals;

    public function __construct()
    {
        $this->builders = [];
        $this->quantities = [];
        $this->processes = [];
        $this->interrupt = false;
        $this->quit = false;
        $this->asyncSignals = false;
    }

    public function addWorker($name, $quantity, ProcessBuilder $builder)
    {
        $this->quantities[$name] = $quantity;
        $this->builders[$name] = $builder;
    }

    public function start()
    {
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            $this->asyncSignals = true;
        }

        pcntl_signal(SIGCHLD, [$this, 'handleSignal']);
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGQUIT, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGUSR1, [$this, 'handleSignal']);

        foreach (array_keys($this->builders) as $name) {
            foreach (range(1, $this->quantities[$name]) as $index) {
                $workerId = $name.$index;

                $this->processes[$workerId] = $this->startWorker($name, $workerId);
            }
        }

        while (true) {
            if ($this->quit) {
                return;
            }

            if (false == $this->asyncSignals) {
                pcntl_signal_dispatch();
            }

            foreach($this->processes as $process) {
                // reads pipes internally.
                $process->getStatus();
            }

            usleep(2000000); // 100ms
        }
    }

    /**
     * @param string $name
     * @param string $workerId
     *
     * @return Process
     */
    private function startWorker($name, $workerId)
    {
        echo sprintf('Starting worker %s', $workerId).PHP_EOL;

        $process = $this->builders[$name]->getProcess();
        $process->start(function($type, $buffer) use ($workerId) {
            echo str_replace(PHP_EOL, PHP_EOL . $workerId . " | ", $buffer);
        });

        if (false == $process->isStarted()) {
            throw new \LogicException(sprintf('Cannot start a worker process %s', $workerId));
        }

        $process->name = $name;
        $process->workerId = $workerId;

        return $process;
    }

    /**
     * @param int $signal
     */
    public function handleSignal($signal)
    {
        switch ($signal) {
            case SIGCHLD:
                if ($this->interrupt || $this->quit) {
                    break;
                }

                foreach ($this->processes as $workerId => $process) {
                    if ($process->isRunning()) {
                        continue;
                    }

                    echo sprintf('Restarting stopped child process %s', $workerId).PHP_EOL;

                    $this->processes[$workerId] = $this->startWorker($process->name, $workerId);
                }

                break;
            case SIGUSR1:
                if ($this->interrupt || $this->quit) {
                    break;
                }

                echo 'Reloading child processes.'.PHP_EOL;

                foreach ($this->processes as $workerId => $process) {
                    if (false == $process->isRunning()) {
                        continue;
                    }

                    $process->stop(5, SIGTERM);
                }

                break;
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
            case SIGINT:   // 2  : ctrl+c
                if ($this->interrupt || $this->quit) {
                    break;
                }

                echo 'Stopping child processes.'.PHP_EOL;

                $this->interrupt = true;

                foreach ($this->processes as $workerId => $process) {
                    $process->signal(SIGTERM);
                }

                $limit = microtime(true) + 3;
                while ($this->processes || microtime(true) < $limit) {
                    foreach ($this->processes as $workerId => $process) {
                        if (false == $process->isRunning()) {
                            unset($this->processes[$workerId]);
                        }
                    }
                }

                if ($this->processes) {
                    foreach ($this->processes as $workerId => $process) {
                        echo sprintf('Killing child process %s', $workerId).PHP_EOL;
                        $process->stop(1, SIGKILL);
                    }
                }

                $this->quit = true;

                break;
            default:
                echo sprintf('Caught signal %d is not handled.', $signal).PHP_EOL;
                break;
        }
    }
}
