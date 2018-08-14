<?php

namespace Enqueue\Consumption;

use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Interop\Queue\PsrQueue;

interface QueueConsumerInterface
{
    /**
     * Milliseconds.
     */
    public function setIdleTimeout(float $timeout): void;

    public function getIdleTimeout(): float;

    /**
     * Milliseconds.
     */
    public function setReceiveTimeout(float $timeout): void;

    public function getReceiveTimeout(): float;

    public function getPsrContext(): PsrContext;

    /**
     * @param string|PsrQueue $queueName
     */
    public function bind($queueName, PsrProcessor $processor): self;

    /**
     * @param string|PsrQueue $queueName
     * @param mixed           $queue
     */
    public function bindCallback($queue, callable $processor): self;

    /**
     * Runtime extension - is an extension or a collection of extensions which could be set on runtime.
     * Here's a good example: @see LimitsExtensionsCommandTrait.
     *
     * @param ExtensionInterface|ChainExtension|null $runtimeExtension
     *
     * @throws \Exception
     */
    public function consume(ExtensionInterface $runtimeExtension = null): void;
}
