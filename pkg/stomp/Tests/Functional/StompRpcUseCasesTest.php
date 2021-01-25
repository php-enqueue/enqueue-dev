<?php

namespace Enqueue\Stomp\Tests\Functional;

use Enqueue\Rpc\Promise;
use Enqueue\Rpc\RpcClient;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompMessage;
use Enqueue\Test\RabbitManagementExtensionTrait;
use Enqueue\Test\RabbitmqStompExtension;

/**
 * @group functional
 */
class StompRpcUseCasesTest extends \PHPUnit\Framework\TestCase
{
    use RabbitManagementExtensionTrait;
    use RabbitmqStompExtension;

    /**
     * @var StompContext
     */
    private $stompContext;

    protected function setUp(): void
    {
        $this->stompContext = $this->buildStompContext();

        $this->removeQueue('stomp.rpc.test');
        $this->removeQueue('stomp.rpc.reply_test');
    }

    protected function tearDown(): void
    {
        $this->stompContext->close();
    }

    public function testDoAsyncRpcCallWithCustomReplyQueue()
    {
        $queue = $this->stompContext->createQueue('stomp.rpc.test');
        $replyQueue = $this->stompContext->createQueue('stomp.rpc.reply_test');
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        $rpcClient = new RpcClient($this->stompContext);

        $message = $this->stompContext->createMessage();
        $message->setReplyTo($replyQueue->getQueueName());

        $promise = $rpcClient->callAsync($queue, $message, 200);
        $this->assertInstanceOf(Promise::class, $promise);

        $consumer = $this->stompContext->createConsumer($queue);
        $message = $consumer->receive(100);
        $this->assertInstanceOf(StompMessage::class, $message);
        $this->assertNotNull($message->getReplyTo());
        $this->assertNotNull($message->getCorrelationId());
        $consumer->acknowledge($message);

        $replyQueue = $this->stompContext->createQueue($message->getReplyTo());
        $replyMessage = $this->stompContext->createMessage('This a reply!');
        $replyMessage->setCorrelationId($message->getCorrelationId());

        $this->stompContext->createProducer()->send($replyQueue, $replyMessage);

        $actualReplyMessage = $promise->receive();
        $this->assertInstanceOf(StompMessage::class, $actualReplyMessage);
    }

    public function testDoAsyncRecCallWithCastInternallyCreatedTemporaryReplyQueue()
    {
        $queue = $this->stompContext->createQueue('stomp.rpc.test');
        $queue->setDurable(true);
        $queue->setAutoDelete(false);

        $rpcClient = new RpcClient($this->stompContext);

        $message = $this->stompContext->createMessage();

        $promise = $rpcClient->callAsync($queue, $message, 200);
        $this->assertInstanceOf(Promise::class, $promise);

        $consumer = $this->stompContext->createConsumer($queue);
        $receivedMessage = $consumer->receive(100);

        $this->assertInstanceOf(StompMessage::class, $receivedMessage);
        $this->assertNotNull($receivedMessage->getReplyTo());
        $this->assertNotNull($receivedMessage->getCorrelationId());
        $consumer->acknowledge($receivedMessage);

        $replyQueue = $this->stompContext->createQueue($receivedMessage->getReplyTo());
        $replyMessage = $this->stompContext->createMessage('This a reply!');
        $replyMessage->setCorrelationId($receivedMessage->getCorrelationId());

        $this->stompContext->createProducer()->send($replyQueue, $replyMessage);

        $actualReplyMessage = $promise->receive();
        $this->assertInstanceOf(StompMessage::class, $actualReplyMessage);
    }
}
