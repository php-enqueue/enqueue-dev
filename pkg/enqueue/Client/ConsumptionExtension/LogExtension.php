<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Result;
use Enqueue\Util\Stringify;
use Psr\Log\LogLevel;

class LogExtension extends \Enqueue\Consumption\Extension\LogExtension
{
    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        $result = $context->getResult();
        $message = $context->getMessage();

        $logLevel = Result::REJECT == ((string) $result) ? LogLevel::ERROR : LogLevel::INFO;

        if ($command = $message->getProperty(Config::COMMAND)) {
            $reason = '';
            $logMessage = "[client] Processed {command}\t{body}\t{result}";
            if ($result instanceof Result && $result->getReason()) {
                $reason = $result->getReason();

                $logMessage .= ' {reason}';
            }

            $context->getLogger()->log($logLevel, $logMessage, [
                'result' => str_replace('enqueue.', '', $result),
                'reason' => $reason,
                'command' => $command,
                'queueName' => $context->getConsumer()->getQueue()->getQueueName(),
                'body' => Stringify::that($message->getBody()),
                'properties' => Stringify::that($message->getProperties()),
                'headers' => Stringify::that($message->getHeaders()),
            ]);

            return;
        }

        $topic = $message->getProperty(Config::TOPIC);
        $processor = $message->getProperty(Config::PROCESSOR);
        if ($topic && $processor) {
            $reason = '';
            $logMessage = "[client] Processed {topic} -> {processor}\t{body}\t{result}";
            if ($result instanceof Result && $result->getReason()) {
                $reason = $result->getReason();

                $logMessage .= ' {reason}';
            }

            $context->getLogger()->log($logLevel, $logMessage, [
                'result' => str_replace('enqueue.', '', $result),
                'reason' => $reason,
                'topic' => $topic,
                'processor' => $processor,
                'queueName' => $context->getConsumer()->getQueue()->getQueueName(),
                'body' => Stringify::that($message->getBody()),
                'properties' => Stringify::that($message->getProperties()),
                'headers' => Stringify::that($message->getHeaders()),
            ]);

            return;
        }

        parent::onPostMessageReceived($context);
    }
}
