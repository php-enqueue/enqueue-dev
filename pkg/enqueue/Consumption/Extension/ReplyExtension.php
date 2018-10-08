<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\Result;

class ReplyExtension implements PostMessageReceivedExtensionInterface
{
    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        $replyTo = $context->getMessage()->getReplyTo();
        if (false == $replyTo) {
            return;
        }

        /** @var Result $result */
        $result = $context->getResult();
        if (false == $result instanceof Result) {
            return;
        }

        if (false == $result->getReply()) {
            return;
        }

        $correlationId = $context->getMessage()->getCorrelationId();
        $replyMessage = clone $result->getReply();
        $replyMessage->setCorrelationId($correlationId);

        $replyQueue = $context->getContext()->createQueue($replyTo);

        $context->getLogger()->debug(sprintf('[ReplyExtension] Send reply to "%s"', $replyTo));
        $context->getContext()->createProducer()->send($replyQueue, $replyMessage);
    }
}
