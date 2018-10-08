<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\ProcessorException;

interface ProcessorExceptionExtensionInterface
{
    /**
     * Execute if a processor throws an exception.
     * The result could be set, if result is not set the exception is thrown again.
     */
    public function onProcessorException(ProcessorException $context): void;
}
