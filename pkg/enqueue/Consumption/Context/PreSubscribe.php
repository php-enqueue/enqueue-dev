<?php

namespace Enqueue\Consumption\Context;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;

final class PreSubscribe
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Context $context, Processor $processor, Consumer $consumer, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->processor = $processor;
        $this->consumer = $consumer;
        $this->logger = $logger;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    public function getConsumer(): Consumer
    {
        return $this->consumer;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
