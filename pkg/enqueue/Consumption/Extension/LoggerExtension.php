<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Consumption\StartExtensionInterface;
use Interop\Queue\Message as InteropMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerExtension implements StartExtensionInterface, PostMessageReceivedExtensionInterface
{
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

    public function onStart(Start $context): void
    {
        if ($context->getLogger() && false == $context->getLogger() instanceof NullLogger) {
            $context->getLogger()->debug(sprintf(
                'Skip setting context\'s logger "%s". Another one "%s" has already been set.',
                get_class($this->logger),
                get_class($context->getLogger())
            ));
        } else {
            $context->changeLogger($this->logger);
            $this->logger->debug(sprintf('Set context\'s logger "%s"', get_class($this->logger)));
        }
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
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
                    $this->logger->error($result->getReason(), $this->messageToLogContext($context->getMessage()));
                }

                break;
            case Result::ACK:
                if ($result->getReason()) {
                    $this->logger->info($result->getReason(), $this->messageToLogContext($context->getMessage()));
                }

                break;
            default:
                throw new \LogicException(sprintf('Got unexpected message result. "%s"', $result->getStatus()));
        }
    }

    /**
     * @param InteropMessage $message
     *
     * @return array
     */
    private function messageToLogContext(InteropMessage $message)
    {
        return [
            'body' => $message->getBody(),
            'headers' => $message->getHeaders(),
            'properties' => $message->getProperties(),
        ];
    }
}
