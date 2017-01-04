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
        $correlationId = $context->getPsrMessage()->getCorrelationId();
        if (false == $replyTo) {
            return;
        }

        /** @var Result $result */
        $result = $context->getResult();
        if (false == $result instanceof Result) {
            throw new \LogicException('To send a reply an instance of Result class has to returned from a Processor.');
        }

        if (false == $result->getReply()) {
            throw new \LogicException('To send a reply the Result must contain a reply message.');
        }

        $replyMessage = clone $result->getReply();
        $replyMessage->setCorrelationId($correlationId);

        $replyQueue = $context->getPsrContext()->createQueue($replyTo);

        $context->getPsrContext()->createProducer()->send($replyQueue, $replyMessage);
    }
}
