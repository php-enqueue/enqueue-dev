<?php

namespace Enqueue\AsyncCommand;

final class RunCommandResult implements \JsonSerializable
{
    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var string
     */
    private $output;

    /**
     * @var string
     */
    private $errorOutput;

    /**
     * @param int    $exitCode
     * @param string $output
     * @param string $errorOutput
     */
    public function __construct($exitCode, $output, $errorOutput)
    {
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->errorOutput = $errorOutput;
    }

    /**
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    public function jsonSerialize()
    {
        return [
            'exitCode' => $this->exitCode,
            'output' => $this->output,
            'errorOutput' => $this->errorOutput,
        ];
    }

    /**
     * @param string $json
     *
     * @return self
     */
    public static function jsonUnserialize($json)
    {
        $data = json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'The malformed json given. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return new self($data['exitCode'], $data['output'], $data['errorOutput']);
    }
}
