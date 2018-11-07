<?php

namespace Enqueue\Client;

use Enqueue\Client\Extension\PrepareBodyExtension;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Util\UUID;

final class Producer implements ProducerInterface
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var ExtensionInterface
     */
    private $extension;

    /**
     * @var RpcFactory
     */
    private $rpcFactory;

    public function __construct(
        DriverInterface $driver,
        RpcFactory $rpcFactory,
        ExtensionInterface $extension = null
    ) {
        $this->driver = $driver;
        $this->rpcFactory = $rpcFactory;

        $this->extension = $extension ?
            new ChainExtension([$extension, new PrepareBodyExtension()]) :
            new ChainExtension([new PrepareBodyExtension()])
        ;
    }

    public function sendEvent(string $topic, $message): void
    {
        if (false == $message instanceof Message) {
            $message = new Message($message);
        }

        $preSend = new PreSend($topic, $message, $this, $this->driver);
        $this->extension->onPreSendEvent($preSend);

        $message = $preSend->getMessage();
        $message->setProperty(Config::TOPIC, $preSend->getTopic());

        $this->doSend($message);
    }

    public function sendCommand(string $command, $message, bool $needReply = false): ?Promise
    {
        if (false == $message instanceof Message) {
            $message = new Message($message);
        }

        $preSend = new PreSend($command, $message, $this, $this->driver);
        $this->extension->onPreSendCommand($preSend);

        $command = $preSend->getCommand();
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

        $message->setProperty(Config::COMMAND, $command);
        $message->setScope(Message::SCOPE_APP);

        $this->doSend($message);

        if ($needReply) {
            $promise = $this->rpcFactory->createPromise($replyTo, $message->getCorrelationId(), 60000);
            $promise->setDeleteReplyQueue($deleteReplyQueue);

            return $promise;
        }

        return null;
    }

    private function doSend(Message $message): void
    {
        if (false === is_string($message->getBody())) {
            throw new \LogicException(sprintf(
                'The message body must be string at this stage, got "%s". Make sure you passed string as message or there is an extension that converts custom input to string.',
                is_object($message->getBody()) ? get_class($message->getBody()) : gettype($message->getBody())
            ));
        }

        if ($message->getProperty(Config::PROCESSOR)) {
            throw new \LogicException(sprintf('The %s property must not be set.', Config::PROCESSOR));
        }

        if (!$message->getMessageId()) {
            $message->setMessageId(UUID::generate());
        }

        if (!$message->getTimestamp()) {
            $message->setTimestamp(time());
        }

        $this->extension->onDriverPreSend(new DriverPreSend($message, $this, $this->driver));

        if (Message::SCOPE_MESSAGE_BUS == $message->getScope()) {
            $result = $this->driver->sendToRouter($message);
        } elseif (Message::SCOPE_APP == $message->getScope()) {
            $result = $this->driver->sendToProcessor($message);
        } else {
            throw new \LogicException(sprintf('The message scope "%s" is not supported.', $message->getScope()));
        }

        $this->extension->onPostSend(new PostSend($message, $this, $this->driver, $result->getTransportDestination(), $result->getTransportMessage()));
    }
}
