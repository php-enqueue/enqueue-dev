<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\EndExtensionInterface;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Consumption\StartExtensionInterface;
use Enqueue\Util\Stringify;
use Psr\Log\LogLevel;

class LogExtension implements StartExtensionInterface, MessageReceivedExtensionInterface, PostMessageReceivedExtensionInterface, EndExtensionInterface
{
    public function onStart(Start $context): void
    {
        $context->getLogger()->debug('Consumption has started');
    }

    public function onEnd(End $context): void
    {
        $context->getLogger()->debug('Consumption has ended');
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        $message = $context->getMessage();

        $context->getLogger()->debug("Received from {queueName}\t{body}", [
            'queueName' => $context->getConsumer()->getQueue()->getQueueName(),
            'redelivered' => $message->isRedelivered(),
            'body' => Stringify::that($message->getBody()),
            'properties' => Stringify::that($message->getProperties()),
            'headers' => Stringify::that($message->getHeaders()),
        ]);
    }

    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        $message = $context->getMessage();
        $queue = $context->getConsumer()->getQueue();
        $result = $context->getResult();

        $reason = '';
        $logMessage = "Processed from {queueName}\t{body}\t{result}";
        if ($result instanceof Result && $result->getReason()) {
            $reason = $result->getReason();
            $logMessage .= ' {reason}';
        }
        $logContext = [
            'result' => str_replace('enqueue.', '', $result),
            'reason' => $reason,
            'queueName' => $queue->getQueueName(),
            'body' => Stringify::that($message->getBody()),
            'properties' => Stringify::that($message->getProperties()),
            'headers' => Stringify::that($message->getHeaders()),
        ];

        $logLevel = Result::REJECT == ((string) $result) ? LogLevel::ERROR : LogLevel::INFO;

        $context->getLogger()->log($logLevel, $logMessage, $logContext);
    }
}
