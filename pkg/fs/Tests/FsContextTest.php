<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsConsumer;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use Enqueue\Fs\FsProducer;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\Context;
use Interop\Queue\Exception\InvalidDestinationException;
use Makasim\File\TempFile;

class FsContextTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(Context::class, FsContext::class);
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new FsContext(sys_get_temp_dir(), 1, 0666, 100);
    }

    public function testShouldAllowCreateEmptyMessage()
    {
        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $message = $context->createMessage();

        $this->assertInstanceOf(FsMessage::class, $message);

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $message = $context->createMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);

        $this->assertInstanceOf(FsMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['aProp' => 'aPropVal'], $message->getProperties());
        $this->assertSame(['aHeader' => 'aHeaderVal'], $message->getHeaders());
    }

    public function testShouldCreateQueue()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $queue = $context->createQueue($tmpFile->getFilename());

        $this->assertInstanceOf(FsDestination::class, $queue);
        $this->assertInstanceOf(\SplFileInfo::class, $queue->getFileInfo());
        $this->assertSame((string) $tmpFile, (string) $queue->getFileInfo());

        $this->assertSame($tmpFile->getFilename(), $queue->getTopicName());
    }

    public function testShouldAllowCreateTopic()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $topic = $context->createTopic($tmpFile->getFilename());

        $this->assertInstanceOf(FsDestination::class, $topic);
        $this->assertInstanceOf(\SplFileInfo::class, $topic->getFileInfo());
        $this->assertSame((string) $tmpFile, (string) $topic->getFileInfo());

        $this->assertSame($tmpFile->getFilename(), $topic->getTopicName());
    }

    public function testShouldAllowCreateTmpQueue()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $queue = $context->createTemporaryQueue();

        $this->assertInstanceOf(FsDestination::class, $queue);
        $this->assertInstanceOf(TempFile::class, $queue->getFileInfo());
        $this->assertNotEmpty($queue->getQueueName());
    }

    public function testShouldCreateProducer()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $producer = $context->createProducer();

        $this->assertInstanceOf(FsProducer::class, $producer);
    }

    public function testShouldThrowIfNotFsDestinationGivenOnCreateConsumer()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Fs\FsDestination but got Enqueue\Null\NullQueue.');
        $consumer = $context->createConsumer(new NullQueue('aQueue'));

        $this->assertInstanceOf(FsConsumer::class, $consumer);
    }

    public function testShouldCreateConsumer()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $queue = $context->createQueue($tmpFile->getFilename());

        $context->createConsumer($queue);
    }

    public function testShouldPropagatePreFetchCountToCreatedConsumer()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 123, 0666, 100);

        $queue = $context->createQueue($tmpFile->getFilename());

        $consumer = $context->createConsumer($queue);

        // guard
        $this->assertInstanceOf(FsConsumer::class, $consumer);

        $this->assertAttributeSame(123, 'preFetchCount', $consumer);
    }

    public function testShouldAllowGetPreFetchCountSetInConstructor()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 123, 0666, 100);

        $this->assertSame(123, $context->getPreFetchCount());
    }

    public function testShouldAllowGetPreviouslySetPreFetchCount()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $context->setPreFetchCount(456);

        $this->assertSame(456, $context->getPreFetchCount());
    }

    public function testShouldAllowPurgeMessagesFromQueue()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        file_put_contents($tmpFile, 'foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $queue = $context->createQueue($tmpFile->getFilename());

        $context->purgeQueue($queue);

        $this->assertEmpty(file_get_contents($tmpFile));
    }

    public function testShouldCreateFileOnFilesystemIfNotExistOnDeclareDestination()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/'.uniqid());

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 100);

        $queue = $context->createQueue($tmpFile->getFilename());

        $this->assertFileDoesNotExist((string) $tmpFile);

        $context->declareDestination($queue);

        $this->assertFileExists((string) $tmpFile);
        $this->assertTrue(is_readable($tmpFile));
        $this->assertTrue(is_writable($tmpFile));

        // do nothing if file already exists
        $context->declareDestination($queue);

        $this->assertFileExists((string) $tmpFile);

        unlink($tmpFile);
    }

    public function testShouldCreateMessageConsumerAndSetPollingInterval()
    {
        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');

        $context = new FsContext(sys_get_temp_dir(), 1, 0666, 123456);

        $queue = $context->createQueue($tmpFile->getFilename());

        $consumer = $context->createConsumer($queue);

        $this->assertInstanceOf(FsConsumer::class, $consumer);
        $this->assertEquals(123456, $consumer->getPollingInterval());
    }
}
