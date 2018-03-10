<?php

namespace Enqueue\Fs\Tests\Functional;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\FsContext;
use Makasim\File\TempFile;
use PHPUnit\Framework\TestCase;

class FsProducerTest extends TestCase
{
    /**
     * @var FsContext
     */
    private $fsContext;

    public function setUp()
    {
        $this->fsContext = (new FsConnectionFactory(['path' => sys_get_temp_dir()]))->createContext();

        new TempFile(sys_get_temp_dir().'/fs_test_queue');
        file_put_contents(sys_get_temp_dir().'/fs_test_queue', '');
    }

    public function tearDown()
    {
        $this->fsContext->close();
    }

    public function testShouldStoreFilesToFileInExpectedFormat()
    {
        $queue = $this->fsContext->createQueue('fs_test_queue');

        $firstMessage = $this->fsContext->createMessage('first message');
        $secondMessage = $this->fsContext->createMessage('second message');
        $thirdMessage = $this->fsContext->createMessage('third message');

        $this->fsContext->createProducer()->send($queue, $firstMessage);
        $this->fsContext->createProducer()->send($queue, $secondMessage);
        $this->fsContext->createProducer()->send($queue, $thirdMessage);

        $this->assertSame(0, strlen(file_get_contents(sys_get_temp_dir().'/fs_test_queue')) % 64);
        $this->assertSame(
            '          |{"body":"first message","properties":[],"headers":[]}         |{"body":"second message","properties":[],"headers":[]}          |{"body":"third message","properties":[],"headers":[]}',
            file_get_contents(sys_get_temp_dir().'/fs_test_queue')
        );
    }

    /**
     * @group bug
     * @group bug390
     */
    public function testThrowIfDelimiterSymbolsFoundInMessageBody()
    {
        $queue = $this->fsContext->createQueue('fs_test_queue');

        $message = $this->fsContext->createMessage('                             |{"body":"aMessageData","properties":{"enqueue.topic_name":"user_updated"},"headers":{"content_type":"text\/plain","message_id":"90979b6c-d9ff-4b39-9938-878b83a95360","timestamp":1519899428,"reply_to":null,"co
rrelation_id":""}}');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The delimiter sequence "|{" found in message body.');
        $this->fsContext->createProducer()->send($queue, $message);
    }
}
