<?php

namespace Enqueue\Client\Extension;

use Enqueue\Client\Message;
use Enqueue\Client\PreSend;
use Enqueue\Client\PreSendCommandExtensionInterface;
use Enqueue\Client\PreSendEventExtensionInterface;
use Enqueue\Util\JSON;

class PrepareBodyExtension implements PreSendEventExtensionInterface, PreSendCommandExtensionInterface
{
    public function onPreSendEvent(PreSend $context): void
    {
        $this->prepareBody($context->getMessage());
    }

    public function onPreSendCommand(PreSend $context): void
    {
        $this->prepareBody($context->getMessage());
    }

    private function prepareBody(Message $message): void
    {
        $body = $message->getBody();
        $contentType = $message->getContentType();

        if (is_scalar($body) || null === $body) {
            $contentType = $contentType ?: 'text/plain';
            $body = (string) $body;
        } elseif (is_array($body)) {
            // only array of scalars is allowed.
            array_walk_recursive($body, function ($value) {
                if (!is_scalar($value) && null !== $value) {
                    throw new \LogicException(sprintf(
                        'The message\'s body must be an array of scalars. Found not scalar in the array: %s',
                        is_object($value) ? get_class($value) : gettype($value)
                    ));
                }
            });

            $contentType = $contentType ?: 'application/json';
            $body = JSON::encode($body);
        } elseif ($body instanceof \JsonSerializable) {
            $contentType = $contentType ?: 'application/json';
            $body = JSON::encode($body);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The message\'s body must be either null, scalar, array or object (implements \JsonSerializable). Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $message->setContentType($contentType);
        $message->setBody($body);
    }
}
