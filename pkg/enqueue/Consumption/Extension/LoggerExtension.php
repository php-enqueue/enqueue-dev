<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\OnStartContext;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $replaceExisting;

    /**
     * @param LoggerInterface $logger
     * @param bool $replaceExisting
     */
    public function __construct(LoggerInterface $logger, $replaceExisting = true)
    {
        $this->logger = $logger;
        $this->replaceExisting = $replaceExisting;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(OnStartContext $context)
    {
        if ($context->getLogger() instanceof NullLogger) {
            $context->setLogger($this->logger);
            $this->logger->debug(sprintf('Set context\'s logger "%s"', get_class($this->logger)));
        } elseif ($this->replaceExisting) {
            $context->setLogger($this->logger);
            $context->getLogger()->debug(sprintf(
                'Replace context\'s logger "%s" with "%s"',
                get_class($context->getLogger()),
                get_class($this->logger)
            ));
        } else {
            $context->getLogger()->debug(sprintf('Skip setting a logger "%s"', get_class($this->logger)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        if (false == $context->getResult() instanceof Result) {
            return;
        }

        /** @var $result Result */
        $result = $context->getResult();

        switch ($result->getStatus()) {
            case Result::REJECT:
            case Result::REQUEUE:
                if ($result->getReason()) {
                    $this->logger->error($result->getReason(), $this->messageToLogContext($context->getPsrMessage()));
                }

                break;
            case Result::ACK:
                if ($result->getReason()) {
                    $this->logger->info($result->getReason(), $this->messageToLogContext($context->getPsrMessage()));
                }

                break;
            default:
                throw new \LogicException(sprintf('Got unexpected message result. "%s"', $result->getStatus()));
        }
    }

    /**
     * @param PsrMessage $message
     *
     * @return array
     */
    private function messageToLogContext(PsrMessage $message)
    {
        return [
            'body' => $message->getBody(),
            'headers' => $message->getHeaders(),
            'properties' => $message->getProperties(),
        ];
    }
}
