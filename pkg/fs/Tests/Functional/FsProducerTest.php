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

    protected function setUp(): void
    {
        $this->fsContext = (new FsConnectionFactory(['path' => sys_get_temp_dir()]))->createContext();

        new TempFile(sys_get_temp_dir().'/fs_test_queue');
        file_put_contents(sys_get_temp_dir().'/fs_test_queue', '');
    }

    protected function tearDown(): void
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
}
