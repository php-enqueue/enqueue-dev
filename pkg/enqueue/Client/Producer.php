<?php

namespace Enqueue\Client;

use Enqueue\Client\Extension\PrepareBodyExtension;
use Enqueue\Rpc\RpcFactory;
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

        $prepareBodyExtension = new PrepareBodyExtension();
        $this->extension = new ChainExtension([$extension, $prepareBodyExtension]) ?: new ChainExtension([$prepareBodyExtension]);
    }

    public function sendEvent($topic, $message)
    {
        if (false == $message instanceof Message) {
            $message = new Message($message);
        }

        $preSend = new PreSend($topic, $message, $this, $this->driver);
        $this->extension->onPreSendEvent($preSend);

        $topic = $preSend->getCommandOrTopic();
        $message = $preSend->getMessage();

        $message->setProperty(Config::PARAMETER_TOPIC_NAME, $topic);

        $this->doSend($message);
    }

    public function sendCommand($command, $message, $needReply = false)
    {
        if (false == $message instanceof Message) {
            $message = new Message($message);
        }

        $preSend = new PreSend($command, $message, $this, $this->driver);
        $this->extension->onPreSendEvent($preSend);

        $command = $preSend->getCommandOrTopic();
        $message = $preSend->getMessage();

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
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, Config::COMMAND_TOPIC);
        $message->setScope(Message::SCOPE_APP);

        $this->doSend($message);

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

    private function doSend(Message $message)
    {
        if (false === is_string($message->getBody())) {
            throw new \LogicException(sprintf(
                'The message body must be string at this stage, got "%s". Make sure you passed string as message or there is an extension that converts custom input to string.',
                is_object($message->getBody()) ? get_class($message->getBody()) : gettype($message->getBody())
            ));
        }

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

            $this->extension->onPreDriverSend(new PreDriverSend($message, $this, $this->driver));
            $this->driver->sendToRouter($message);
        } elseif (Message::SCOPE_APP == $message->getScope()) {
            if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
                $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $this->driver->getConfig()->getRouterProcessorName());
            }
            if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
                $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $this->driver->getConfig()->getRouterQueueName());
            }

            $this->extension->onPreDriverSend(new PreDriverSend($message, $this, $this->driver));
            $this->driver->sendToRouter($message);
        } else {
            throw new \LogicException(sprintf('The message scope "%s" is not supported.', $message->getScope()));
        }

        $this->extension->onPostSend(new PostSend($message, $this, $this->driver));
    }
}
