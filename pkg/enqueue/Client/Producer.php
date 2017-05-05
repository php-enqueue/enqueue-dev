<?php

namespace Enqueue\Client;

use Enqueue\Util\JSON;
use Enqueue\Util\UUID;

class Producer implements ProducerInterface
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var ExtensionInterface
     */
    private $extension;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver, ExtensionInterface $extension = null)
    {
        $this->driver = $driver;
        $this->extension = $extension ?: new ChainExtension([]);
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if (false == $message instanceof Message) {
            $body = $message;
            $message = new Message();
            $message->setBody($body);
        }

        $this->prepareBody($message);

        $message->setProperty(Config::PARAMETER_TOPIC_NAME, $topic);

        if (!$message->getMessageId()) {
            $message->setMessageId(UUID::generate());
        }

        if (!$message->getTimestamp()) {
            $message->setTimestamp(time());
        }

        if (!$message->getPriority()) {
            $message->setPriority(MessagePriority::NORMAL);
        }

        $this->extension->onPreSend($topic, $message);

        if (Message::SCOPE_MESSAGE_BUS == $message->getScope()) {
            if ($message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
                throw new \LogicException(sprintf('The %s property must not be set for messages that are sent to message bus.', Config::PARAMETER_PROCESSOR_QUEUE_NAME));
            }
            if ($message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
                throw new \LogicException(sprintf('The %s property must not be set for messages that are sent to message bus.', Config::PARAMETER_PROCESSOR_NAME));
            }

            $this->driver->sendToRouter($message);
        } elseif (Message::SCOPE_APP == $message->getScope()) {
            if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
                $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $this->driver->getConfig()->getRouterProcessorName());
            }
            if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
                $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $this->driver->getConfig()->getRouterQueueName());
            }

            $this->driver->sendToProcessor($message);
        } else {
            throw new \LogicException(sprintf('The message scope "%s" is not supported.', $message->getScope()));
        }

        $this->extension->onPostSend($topic, $message);
    }

    /**
     * @param Message $message
     */
    private function prepareBody(Message $message)
    {
        $body = $message->getBody();
        $contentType = $message->getContentType();

        if (is_scalar($body) || null === $body) {
            $contentType = $contentType ?: 'text/plain';
            $body = (string) $body;
        } elseif (is_array($body)) {
            if ($contentType && $contentType !== 'application/json') {
                throw new \LogicException(sprintf('Content type "application/json" only allowed when body is array'));
            }

            // only array of scalars is allowed.
            array_walk_recursive($body, function ($value) {
                if (!is_scalar($value) && null !== $value) {
                    throw new \LogicException(sprintf(
                        'The message\'s body must be an array of scalars. Found not scalar in the array: %s',
                        is_object($value) ? get_class($value) : gettype($value)
                    ));
                }
            });

            $contentType = 'application/json';
            $body = JSON::encode($body);
        } elseif ($body instanceof \JsonSerializable) {
            if ($contentType && $contentType !== 'application/json') {
                throw new \LogicException(sprintf('Content type "application/json" only allowed when body is array'));
            }

            $contentType = 'application/json';
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
