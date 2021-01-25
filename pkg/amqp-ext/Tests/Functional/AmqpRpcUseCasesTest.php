<?php

namespace Enqueue\AmqpExt\Tests\Functional;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\RpcClient;
use Enqueue\Test\RabbitManagementExtensionTrait;
use Enqueue\Test\RabbitmqAmqpExtension;
use Interop\Amqp\Impl\AmqpMessage;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class AmqpRpcUseCasesTest extends TestCase
{
    use RabbitManagementExtensionTrait;
    use RabbitmqAmqpExtension;

    /**
     * @var AmqpContext
     */
    private $amqpContext;

    protected function setUp(): void
    {
        $this->amqpContext = $this->buildAmqpContext();

        $this->removeQueue('rpc.test');
        $this->removeQueue('rpc.reply_test');
    }

    protected function tearDown(): void
    {
        $this->amqpContext->close();
    }

    public function testDoAsyncRpcCallWithCustomReplyQueue()
    {
        $queue = $this->amqpContext->createQueue('rpc.test');
        $this->amqpContext->declareQueue($queue);

        $replyQueue = $this->amqpContext->createQueue('rpc.reply_test');
        $this->amqpContext->declareQueue($replyQueue);

        $rpcClient = new RpcClient($this->amqpContext);

        $message = $this->amqpContext->createMessage();
        $message->setReplyTo($replyQueue->getQueueName());

        $promise = $rpcClient->callAsync($queue, $message, 10);
        $this->assertInstanceOf(Promise::class, $promise);

        $consumer = $this->amqpContext->createConsumer($queue);
        $message = $consumer->receive(1000);
        $this->assertInstanceOf(AmqpMessage::class, $message);
        $this->assertNotNull($message->getReplyTo());
        $this->assertNotNull($message->getCorrelationId());
        $consumer->acknowledge($message);

        $replyQueue = $this->amqpContext->createQueue($message->getReplyTo());
        $replyMessage = $this->amqpContext->createMessage('This a reply!');
        $replyMessage->setCorrelationId($message->getCorrelationId());

        $this->amqpContext->createProducer()->send($replyQueue, $replyMessage);

        $actualReplyMessage = $promise->receive();
        $this->assertInstanceOf(AmqpMessage::class, $actualReplyMessage);
    }

    public function testDoAsyncRecCallWithCastInternallyCreatedTemporaryReplyQueue()
    {
        $queue = $this->amqpContext->createQueue('rpc.test');
        $this->amqpContext->declareQueue($queue);

        $rpcClient = new RpcClient($this->amqpContext);

        $message = $this->amqpContext->createMessage();

        $promise = $rpcClient->callAsync($queue, $message, 10);
        $this->assertInstanceOf(Promise::class, $promise);

        $consumer = $this->amqpContext->createConsumer($queue);
        $receivedMessage = $consumer->receive(1000);

        $this->assertInstanceOf(AmqpMessage::class, $receivedMessage);
        $this->assertNotNull($receivedMessage->getReplyTo());
        $this->assertNotNull($receivedMessage->getCorrelationId());
        $consumer->acknowledge($receivedMessage);

        $replyQueue = $this->amqpContext->createQueue($receivedMessage->getReplyTo());
        $replyMessage = $this->amqpContext->createMessage('This a reply!');
        $replyMessage->setCorrelationId($receivedMessage->getCorrelationId());

        $this->amqpContext->createProducer()->send($replyQueue, $replyMessage);

        $actualReplyMessage = $promise->receive();
        $this->assertInstanceOf(AmqpMessage::class, $actualReplyMessage);
    }
}
