<?php
namespace Enqueue\Symfony;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class ConsumeDaemon
{
    /**
     * @var ProcessBuilder
     */
    private $workerBuilder;

    /**
     * @var Process[]
     */
    private $workers;

    /**
     * @param ProcessBuilder $workerBuilder
     */
    public function __construct(ProcessBuilder $workerBuilder)
    {
        $this->workerBuilder = $workerBuilder;
        $this->workers = [];
        $this->interrupt = false;
    }

    public function start($workersNumber)
    {
        /** @var Process[] $workers */
        $workers = [];

        $handleSignal = function($signal) use (&$workers) {
            switch ($signal) {
                case SIGUSR1:
                    echo 'Daemon is reloading now.'.PHP_EOL;

                    foreach ($workers as $id => $worker) {
                        $worker->signal(SIGQUIT);
                    }

                    break;
                case SIGTERM:  // 15 : supervisor default stop
                case SIGQUIT:  // 3  : kill -s QUIT
                case SIGINT:   // 2  : ctrl+c
                    foreach ($workers as $id => $worker) {
                        $worker->stop(3, SIGQUIT);
                    }

                    while ($workers) {
                        foreach ($workers as $id => $worker) {
                            if (false == $worker->isRunning()) {
                                unset($workers[$id]);
                            }
                        }
                    }

                    exit;

                    break;
                default:
                    break;
            }
        };

        pcntl_signal(SIGTERM, $handleSignal);
        pcntl_signal(SIGQUIT, $handleSignal);
        pcntl_signal(SIGINT, $handleSignal);
        pcntl_signal(SIGUSR1, $handleSignal);

        foreach (range(1, $workersNumber) as $id) {
            $workers[] = $this->startWorker($id);
        }

        while (true) {
            pcntl_signal_dispatch();

            foreach ($workers as $id => $worker) {
                if (false == $worker->isRunning()) {
                    echo sprintf('Worker %s exited with status %d', $id, $worker->getExitCode()).PHP_EOL;

                    unset($this->workers[$id]);
                    $workers[$id] = $this->startWorker($id);
                }
            }

            pcntl_signal_dispatch();

            sleep(1);
        }
    }

    /**
     * @param int $workerId
     *
     * @return Process
     */
    public function startWorker($workerId)
    {
        if (array_key_exists($workerId, $this->workers)) {
            throw new \LogicException(sprintf('Such worker %s is already in pool.', $workerId));
        }

        echo sprintf('Start worker %s', $workerId).PHP_EOL;

        $process = $this->workerBuilder->getProcess();
        $process->start(function($type, $buffer) use ($workerId) {
            echo $workerId.' | '.$buffer;
        });

        if (false == $process->isStarted()) {
            throw new \LogicException('Cannot start a worker process.');
        }

        return $process;
    }
}
