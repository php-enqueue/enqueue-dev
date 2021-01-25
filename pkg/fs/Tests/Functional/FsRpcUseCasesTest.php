<?php

namespace Enqueue\Fs\Tests\Functional;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsMessage;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\RpcClient;
use Makasim\File\TempFile;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class FsRpcUseCasesTest extends TestCase
{
    /**
     * @var FsContext
     */
    private $fsContext;

    protected function setUp(): void
    {
        $this->fsContext = (new FsConnectionFactory(['path' => sys_get_temp_dir()]))->createContext();

        new TempFile(sys_get_temp_dir().'/fs_rpc_queue');
        new TempFile(sys_get_temp_dir().'/fs_reply_queue');
    }

    protected function tearDown(): void
    {
        $this->fsContext->close();
    }

    public function testDoAsyncRpcCallWithCustomReplyQueue()
    {
        $queue = $this->fsContext->createQueue('fs_rpc_queue');

        $replyQueue = $this->fsContext->createQueue('fs_reply_queue');

        $rpcClient = new RpcClient($this->fsContext);

        $message = $this->fsContext->createMessage();
        $message->setReplyTo($replyQueue->getQueueName());

        $promise = $rpcClient->callAsync($queue, $message, 10);
        $this->assertInstanceOf(Promise::class, $promise);

        $consumer = $this->fsContext->createConsumer($queue);
        $message = $consumer->receive(1);
        $this->assertInstanceOf(FsMessage::class, $message);
        $this->assertNotNull($message->getReplyTo());
        $this->assertNotNull($message->getCorrelationId());
        $consumer->acknowledge($message);

        $replyQueue = $this->fsContext->createQueue($message->getReplyTo());
        $replyMessage = $this->fsContext->createMessage('This a reply!');
        $replyMessage->setCorrelationId($message->getCorrelationId());

        $this->fsContext->createProducer()->send($replyQueue, $replyMessage);

        $actualReplyMessage = $promise->receive();
        $this->assertInstanceOf(FsMessage::class, $actualReplyMessage);
    }

    public function testDoAsyncRecCallWithCastInternallyCreatedTemporaryReplyQueue()
    {
        $queue = $this->fsContext->createQueue('fs_rpc_queue');

        $rpcClient = new RpcClient($this->fsContext);

        $message = $this->fsContext->createMessage();

        $promise = $rpcClient->callAsync($queue, $message, 10);
        $this->assertInstanceOf(Promise::class, $promise);

        $consumer = $this->fsContext->createConsumer($queue);
        $receivedMessage = $consumer->receive(1);

        $this->assertInstanceOf(FsMessage::class, $receivedMessage);
        $this->assertNotNull($receivedMessage->getReplyTo());
        $this->assertNotNull($receivedMessage->getCorrelationId());
        $consumer->acknowledge($receivedMessage);

        $replyQueue = $this->fsContext->createQueue($receivedMessage->getReplyTo());
        $replyMessage = $this->fsContext->createMessage('This a reply!');
        $replyMessage->setCorrelationId($receivedMessage->getCorrelationId());

        $this->fsContext->createProducer()->send($replyQueue, $replyMessage);

        $actualReplyMessage = $promise->receive();
        $this->assertInstanceOf(FsMessage::class, $actualReplyMessage);
    }
}
