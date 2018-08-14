<?php

namespace Enqueue\AsyncCommand;

final class RunCommand implements \JsonSerializable
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var string[]
     */
    private $arguments;

    /**
     * @var string[]
     */
    private $options;

    /**
     * @param string   $command
     * @param string[] $arguments
     * @param string[] $options
     */
    public function __construct(string $command, array $arguments = [], array $options = [])
    {
        $this->command = $command;
        $this->arguments = $arguments;
        $this->options = $options;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function jsonSerialize(): array
    {
        return [
            'command' => $this->command,
            'arguments' => $this->arguments,
            'options' => $this->options,
        ];
    }

    public static function jsonUnserialize(string $json): self
    {
        $data = json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'The malformed json given. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return new self($data['command'], $data['arguments'], $data['options']);
    }
}
