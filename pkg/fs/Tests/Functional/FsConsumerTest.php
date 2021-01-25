<?php

namespace Enqueue\Fs\Tests\Functional;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use PHPUnit\Framework\TestCase;

class FsConsumerTest extends TestCase
{
    /**
     * @var FsContext
     */
    private $fsContext;

    protected function setUp(): void
    {
        $this->fsContext = (new FsConnectionFactory(['path' => sys_get_temp_dir()]))->createContext();

        $this->fsContext->purgeQueue($this->fsContext->createQueue('fs_test_queue'));
    }

    protected function tearDown(): void
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
     * @group bug170
     */
    public function testShouldNotFailOnSpecificMessageSize()
    {
        $context = $this->fsContext;
        $queue = $context->createQueue('fs_test_queue');
        $context->purgeQueue($queue);

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

    /**
     * @group bug
     * @group bug170
     */
    public function testShouldNotCorruptFrameSize()
    {
        $context = $this->fsContext;
        $queue = $context->createQueue('fs_test_queue');
        $context->purgeQueue($queue);

        $consumer = $context->createConsumer($queue);
        $producer = $context->createProducer();

        $producer->send($queue, $context->createMessage(str_repeat('a', 23)));
        $producer->send($queue, $context->createMessage(str_repeat('b', 24)));

        $message = $consumer->receiveNoWait();
        $this->assertNotNull($message);
        $context->workWithFile($queue, 'a+', function (FsDestination $destination, $file) {
            $this->assertSame(0, fstat($file)['size'] % 64);
        });

        $message = $consumer->receiveNoWait();
        $this->assertNotNull($message);
        $context->workWithFile($queue, 'a+', function (FsDestination $destination, $file) {
            $this->assertSame(0, fstat($file)['size'] % 64);
        });

        $message = $consumer->receiveNoWait();
        $this->assertNull($message);
    }

    /**
     * @group bug
     * @group bug202
     */
    public function testShouldThrowExceptionForTheCorruptedQueueFile()
    {
        $context = $this->fsContext;
        $queue = $context->createQueue('fs_test_queue');
        $context->purgeQueue($queue);

        $context->workWithFile($queue, 'a+', function (FsDestination $destination, $file) {
            fwrite($file, '|{"body":"{\"path\":\"\\\/p\\\/r\\\/pr_swoppad_6_4910_red_1.jpg\",\"filters\":null,\"force\":false}","properties":{"enqueue.topic_name":"liip_imagine_resolve_cache"},"headers":{"content_type":"application\/json","message_id":"46fdc345-5d0c-426e-95ac-227c7e657839","timestamp":1505379216,"reply_to":null,"correlation_id":""}}                                                          |{"body":"{\"path\":\"\\\/p\\\/r\\\/pr_swoppad_6_4910_black_1.jpg\",\"filters\":null,\"force\":false}","properties":{"enqueue.topic_name":"liip_imagine_resolve_cache"},"headers":{"content_type":"application\/json","message_id":"c4d60e39-3a8c-42df-b536-c8b7c13e006d","timestamp":1505379216,"reply_to":null,"correlation_id":""}}                                                          |{"body":"{\"path\":\"\\\/p\\\/r\\\/pr_swoppad_6_4910_green_1.jpg\",\"filters\":null,\"force\":false}","properties":{"enqueue.topic_name":"liip_imagine_resolve_cache"},"headers":{"content_type":"application\/json","message_id":"3a6aa176-c879-4435-9626-c48e0643defa","timestamp":1505379216,"reply_to":null,"correlation_id":""}}');
        });

        $consumer = $context->createConsumer($queue);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The frame could start from either " " or "|". The malformed frame starts with """.');
        $consumer->receiveNoWait();
    }

    /**
     * @group bug
     * @group bug202
     */
    public function testShouldThrowExceptionWhenFrameSizeNotDivideExactly()
    {
        $context = $this->fsContext;
        $queue = $context->createQueue('fs_test_queue');
        $context->purgeQueue($queue);

        $context->workWithFile($queue, 'a+', function (FsDestination $destination, $file) {
            $msg = '|{"body":""}';
            //guard
            $this->assertNotSame(0, strlen($msg) % 64);

            fwrite($file, $msg);
        });

        $consumer = $context->createConsumer($queue);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The frame size is "12" and it must divide exactly to 64 but it leaves a reminder "12".');
        $consumer->receiveNoWait();
    }

    /**
     * @group bug
     * @group bug390
     */
    public function testShouldUnEscapeDelimiterSymbolsInMessageBody()
    {
        $context = $this->fsContext;
        $queue = $context->createQueue('fs_test_queue');
        $context->purgeQueue($queue);

        $message = $this->fsContext->createMessage('                             |{"body":"aMessageData","properties":{"enqueue.topic_name":"user_updated"},"headers":{"content_type":"text\/plain","message_id":"90979b6c-d9ff-4b39-9938-878b83a95360","timestamp":1519899428,"reply_to":null,"correlation_id":""}}');

        $this->fsContext->createProducer()->send($queue, $message);

        $this->assertSame(0, strlen(file_get_contents(sys_get_temp_dir().'/fs_test_queue')) % 64);
        $this->assertSame(
            '                                                       |{"body":"                             \|\{\"body\":\"aMessageData\",\"properties\":{\"enqueue.topic_name\":\"user_updated\"},\"headers\":{\"content_type\":\"text\\\\\/plain\",\"message_id\":\"90979b6c-d9ff-4b39-9938-878b83a95360\",\"timestamp\":1519899428,\"reply_to\":null,\"correlation_id\":\"\"}}","properties":[],"headers":[]}',
            file_get_contents(sys_get_temp_dir().'/fs_test_queue')
        );

        $consumer = $context->createConsumer($queue);

        $message = $consumer->receiveNoWait();

        $this->assertSame('                             |{"body":"aMessageData","properties":{"enqueue.topic_name":"user_updated"},"headers":{"content_type":"text\/plain","message_id":"90979b6c-d9ff-4b39-9938-878b83a95360","timestamp":1519899428,"reply_to":null,"correlation_id":""}}', $message->getBody());
    }
}
