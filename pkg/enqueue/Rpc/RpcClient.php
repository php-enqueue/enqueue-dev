<?php

namespace Enqueue\Rpc;

use Enqueue\Util\UUID;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;

class RpcClient
{
    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var RpcFactory
     */
    private $rpcFactory;

    /**
     * @param PsrContext $context
     * @param RpcFactory $promiseFactory
     */
    public function __construct(PsrContext $context, RpcFactory $promiseFactory = null)
    {
        $this->context = $context;
        $this->rpcFactory = $promiseFactory ?: new RpcFactory($context);
    }

    /**
     * @param PsrDestination $destination
     * @param PsrMessage     $message
     * @param int            $timeout
     *
     * @throws TimeoutException if the wait timeout is reached
     *
     * @return PsrMessage
     */
    public function call(PsrDestination $destination, PsrMessage $message, $timeout)
    {
        return $this->callAsync($destination, $message, $timeout)->receive();
    }

    /**
     * @param PsrDestination $destination
     * @param PsrMessage     $message
     * @param int            $timeout
     *
     * @return Promise
     */
    public function callAsync(PsrDestination $destination, PsrMessage $message, $timeout)
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
