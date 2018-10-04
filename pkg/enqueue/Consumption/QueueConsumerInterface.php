<?php

namespace Enqueue\Consumption;

use Interop\Queue\Context;
use Interop\Queue\Processor;
use Interop\Queue\Queue as InteropQueue;

interface QueueConsumerInterface
{
    /**
     * In milliseconds.
     */
    public function setIdleTime(int $time): void;

    /**
     * In milliseconds.
     */
    public function getIdleTime(): int;

    /**
     * In milliseconds.
     */
    public function setReceiveTimeout(int $timeout): void;

    /**
     * In milliseconds.
     */
    public function getReceiveTimeout(): int;

    public function getContext(): Context;

    /**
     * @param string|InteropQueue $queueName
     */
    public function bind($queueName, Processor $processor): self;

    /**
     * @param string|InteropQueue $queueName
     * @param mixed               $queue
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
