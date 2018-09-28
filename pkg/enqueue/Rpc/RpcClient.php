<?php

namespace Enqueue\Rpc;

use Enqueue\Util\UUID;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Message as InteropMessage;

class RpcClient
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var RpcFactory
     */
    private $rpcFactory;

    /**
     * @param Context    $context
     * @param RpcFactory $promiseFactory
     */
    public function __construct(Context $context, RpcFactory $promiseFactory = null)
    {
        $this->context = $context;
        $this->rpcFactory = $promiseFactory ?: new RpcFactory($context);
    }

    /**
     * @param Destination    $destination
     * @param InteropMessage $message
     * @param int            $timeout
     *
     * @throws TimeoutException if the wait timeout is reached
     *
     * @return InteropMessage
     */
    public function call(Destination $destination, InteropMessage $message, $timeout)
    {
        return $this->callAsync($destination, $message, $timeout)->receive();
    }

    /**
     * @param Destination    $destination
     * @param InteropMessage $message
     * @param int            $timeout
     *
     * @return Promise
     */
    public function callAsync(Destination $destination, InteropMessage $message, $timeout)
    {
        if ($timeout < 1) {
            throw new \InvalidArgumentException(sprintf('Timeout must be positive not zero integer. Got %s', $timeout));
        }

        $deleteReplyQueue = false;
        $replyTo = $message->getReplyTo();

        if (false == $replyTo) {
            $message->setReplyTo($replyTo = $this->rpcFactory->createReplyTo());
            $deleteReplyQueue = true;
        }

        if (false == $message->getCorrelationId()) {
            $message->setCorrelationId(UUID::generate());
        }

        $this->context->createProducer()->send($destination, $message);

        $promise = $this->rpcFactory->createPromise($replyTo, $message->getCorrelationId(), $timeout);
        $promise->setDeleteReplyQueue($deleteReplyQueue);

        return $promise;
    }
}
