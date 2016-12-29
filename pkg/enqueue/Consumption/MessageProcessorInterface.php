<?php
namespace Enqueue\Consumption;

use Enqueue\Psr\Context as PsrContext;
use Enqueue\Psr\Message as PsrMessage;

interface MessageProcessorInterface
{
    /**
     * @param PsrMessage $message
     * @param PsrContext $context
     *
     * @return string
     */
    public function process(PsrMessage $message, PsrContext $context);
}
