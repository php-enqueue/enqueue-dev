<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;

class ReplyExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $replyTo = $context->getPsrMessage()->getReplyTo();
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

        $correlationId = $context->getPsrMessage()->getCorrelationId();
        $replyMessage = clone $result->getReply();
        $replyMessage->setCorrelationId($correlationId);

        $replyQueue = $context->getPsrContext()->createQueue($replyTo);

        $context->getLogger()->debug(sprintf('[ReplyExtension] Send reply to "%s"', $replyTo));
        $context->getPsrContext()->createProducer()->send($replyQueue, $replyMessage);
    }
}
