<?php

namespace Enqueue\Symfony\Creator;

use Enqueue\Client\ProducerInterface;

class JobCommandCreator
{
    /**
     * @var ProducerInterface
     */
    protected $producer;

    /**
     * @var string
     */
    protected $env;

    /**
     * CommandJobCreator constructor.
     *
     * @param ProducerInterface $producer
     * @param string            $env
     */
    public function __construct(ProducerInterface $producer, $env)
    {
        $this->producer = $producer;
        $this->env = $env;
    }

    /**
     * @param $command
     * @param mixed $args
     *
     * @return \Enqueue\Rpc\Promise|null
     */
    public function scheduleCommand($command, $args = [])
    {
        $argumentString = $this->createArgumentString($args);

        return $this->producer->sendCommand('run_command', sprintf('%s %s', $command, $argumentString));
    }

    /**
     * @param array $arguments
     *
     * @return string
     */
    public function createArgumentString(array $arguments)
    {
        $optionList = [];

        foreach ($arguments as $key => $value) {
            if (!is_int($key)) {
                $optionList[] = sprintf('--%s=%s', $key, $value);
                continue;
            }

            $optionList[] = sprintf('%s', $value);
        }

        $optionList[] = sprintf('--env=%s', $this->env);

        return implode(' ', $optionList);
    }
}
