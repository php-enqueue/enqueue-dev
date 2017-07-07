<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrMessage;
use Psr\Log\LoggerInterface;

class LoggerExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        $context->setLogger($this->logger);
        $this->logger->debug(sprintf('Set context\'s logger %s', get_class($this->logger)));
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
