<?php

namespace Enqueue\Fs\Tests\Functional;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsMessage;
use PHPUnit\Framework\TestCase;

class FsConsumerTest extends TestCase
{
    /**
     * @var FsContext
     */
    private $fsContext;

    public function setUp()
    {
        $this->fsContext = (new FsConnectionFactory(['path' => sys_get_temp_dir()]))->createContext();

        $this->fsContext->purge($this->fsContext->createQueue('fs_test_queue'));
    }

    public function tearDown()
    {
        $this->fsContext->close();
    }

    public function testShouldConsumeMessagesFromFileOneByOne()
    {
        $queue = $this->fsContext->createQueue('fs_test_queue');

        file_put_contents(
            sys_get_temp_dir().'/fs_test_queue',
            '          |{"body":"first message","properties":[],"headers":[]}         |{"body":"second message","properties":[],"headers":[]}          |{"body":"third message","properties":[],"headers":[]}'
        );

        $consumer = $this->fsContext->createConsumer($queue);

        $message = $consumer->receiveNoWait();
        $this->assertInstanceOf(FsMessage::class, $message);
        $this->assertSame('third message', $message->getBody());

        $this->assertSame(
            '          |{"body":"first message","properties":[],"headers":[]}         |{"body":"second message","properties":[],"headers":[]}',
            file_get_contents(sys_get_temp_dir().'/fs_test_queue')
        );

        $message = $consumer->receiveNoWait();
        $this->assertInstanceOf(FsMessage::class, $message);
        $this->assertSame('second message', $message->getBody());

        $this->assertSame(
            '          |{"body":"first message","properties":[],"headers":[]}',
            file_get_contents(sys_get_temp_dir().'/fs_test_queue')
        );

        $message = $consumer->receiveNoWait();
        $this->assertInstanceOf(FsMessage::class, $message);
        $this->assertSame('first message', $message->getBody());

        $this->assertEmpty(file_get_contents(sys_get_temp_dir().'/fs_test_queue'));

        $message = $consumer->receiveNoWait();
        $this->assertNull($message);

        $this->assertEmpty(file_get_contents(sys_get_temp_dir().'/fs_test_queue'));
    }

    /**
     * @group bug
     */
    public function testShouldNotFailOnSpecificMessageSize()
    {
        $context = $this->fsContext;
        $queue = $context->createQueue('fs_test_queue');
        $context->purge($queue);

        $consumer = $context->createConsumer($queue);
        $producer = $context->createProducer();

        $producer->send($queue, $context->createMessage(str_repeat('a', 23)));
        $producer->send($queue, $context->createMessage(str_repeat('b', 24)));

        $message = $consumer->receiveNoWait();
        $this->assertSame(str_repeat('b', 24), $message->getBody());

        $message = $consumer->receiveNoWait();
        $this->assertSame(str_repeat('a', 23), $message->getBody());

        $message = $consumer->receiveNoWait();
        $this->assertNull($message);
    }
}
