<?php

namespace Enqueue\Client;

use Enqueue\Rpc\RpcFactory;
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
     * @var RpcFactory
     */
    private $rpcFactory;

    /**
     * @param DriverInterface         $driver
     * @param ExtensionInterface|null $extension
     * @param RpcFactory              $rpcFactory
     *
     * @internal param RpcClient $rpcClient
     */
    public function __construct(
        DriverInterface $driver,
        RpcFactory $rpcFactory,
        ExtensionInterface $extension = null
    ) {
        $this->driver = $driver;
        $this->rpcFactory = $rpcFactory;
        $this->extension = $extension ?: new ChainExtension([]);
    }

    /**
     * {@inheritdoc}
     */
    public function sendEvent($topic, $message)
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

        if (Message::SCOPE_MESSAGE_BUS == $message->getScope()) {
            if ($message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
                throw new \LogicException(sprintf('The %s property must not be set for messages that are sent to message bus.', Config::PARAMETER_PROCESSOR_QUEUE_NAME));
            }
            if ($message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
                throw new \LogicException(sprintf('The %s property must not be set for messages that are sent to message bus.', Config::PARAMETER_PROCESSOR_NAME));
            }

            $this->extension->onPreSend($topic, $message);
            $this->driver->sendToRouter($message);
            $this->extension->onPostSend($topic, $message);
        } elseif (Message::SCOPE_APP == $message->getScope()) {
            if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
                $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $this->driver->getConfig()->getRouterProcessorName());
            }
            if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
                $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $this->driver->getConfig()->getRouterQueueName());
            }

            $this->extension->onPreSend($topic, $message);
            $this->driver->sendToProcessor($message);
            $this->extension->onPostSend($topic, $message);
        } else {
            throw new \LogicException(sprintf('The message scope "%s" is not supported.', $message->getScope()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendCommand($command, $message, $needReply = false)
    {
        if (false == $message instanceof Message) {
            $message = new Message($message);
        }

        $deleteReplyQueue = false;
        $replyTo = $message->getReplyTo();

        if ($needReply) {
            if (false == $replyTo) {
                $message->setReplyTo($replyTo = $this->rpcFactory->createReplyTo());
                $deleteReplyQueue = true;
            }

            if (false == $message->getCorrelationId()) {
                $message->setCorrelationId(UUID::generate());
            }
        }

        $message->setProperty(Config::PARAMETER_TOPIC_NAME, Config::COMMAND_TOPIC);
        $message->setProperty(Config::PARAMETER_COMMAND_NAME, $command);
        $message->setScope(Message::SCOPE_APP);

        $this->sendEvent(Config::COMMAND_TOPIC, $message);

        if ($needReply) {
            $promise = $this->rpcFactory->createPromise($replyTo, $message->getCorrelationId(), 60000);
            $promise->setDeleteReplyQueue($deleteReplyQueue);

            return $promise;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->sendEvent($topic, $message);
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
