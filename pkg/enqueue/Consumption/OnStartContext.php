<?php
namespace Enqueue\Consumption;


use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class OnStartContext
{
    /**
     * @var PsrContext
     */
    private $psrContext;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var callable[]|PsrProcessor[]
     */
    private $processors;
    /**
     * @var PsrConsumer[]
     */
    private $consumers;

    public function __construct(PsrContext $psrContext, LoggerInterface $logger, array $processors, array $consumers)
    {
        $this->psrContext = $psrContext;
        $this->logger = $logger;
        $this->processors = $processors;
        $this->consumers = $consumers;
    }

    /**
     * @return callable[]|PsrProcessor[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * @param callable[]|PsrProcessor[] $processors
     */
    public function setProcessors($processors)
    {
        $this->processors = $processors;
    }

    /**
     * @return PsrConsumer[]
     */
    public function getConsumers()
    {
        return $this->consumers;
    }

    /**
     * @param PsrConsumer[] $consumers
     */
    public function setConsumers(array $consumers)
    {
        $this->consumers = $consumers;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return PsrContext
     */
    public function getPsrContext()
    {
        return $this->psrContext;
    }
}