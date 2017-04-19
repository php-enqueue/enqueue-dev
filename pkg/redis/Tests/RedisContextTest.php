<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\RedisConsumer;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\RedisProducer;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrContext;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullQueue;
use Makasim\File\TempFile;

class RedisContextTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(PsrContext::class, RedisContext::class);
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new RedisContext($this->createRedisMock());
    }

//    public function testShouldAllowCreateEmptyMessage()
//    {
//        $context = new RedisContext($this->createRedisMock());
//
//        $message = $context->createMessage();
//
//        $this->assertInstanceOf(RedisMessage::class, $message);
//
//        $this->assertSame('', $message->getBody());
//        $this->assertSame([], $message->getProperties());
//        $this->assertSame([], $message->getHeaders());
//    }
//
//    public function testShouldAllowCreateCustomMessage()
//    {
//        $context = new RedisContext($this->createRedisMock());
//
//        $message = $context->createMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);
//
//        $this->assertInstanceOf(RedisMessage::class, $message);
//
//        $this->assertSame('theBody', $message->getBody());
//        $this->assertSame(['aProp' => 'aPropVal'], $message->getProperties());
//        $this->assertSame(['aHeader' => 'aHeaderVal'], $message->getHeaders());
//    }
//
//    public function testShouldCreateQueue()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $queue = $context->createQueue($tmpFile->getFilename());
//
//        $this->assertInstanceOf(RedisDestination::class, $queue);
//        $this->assertInstanceOf(\SplFileInfo::class, $queue->getFileInfo());
//        $this->assertSame((string) $tmpFile, (string) $queue->getFileInfo());
//
//        $this->assertSame($tmpFile->getFilename(), $queue->getTopicName());
//    }
//
//    public function testShouldAllowCreateTopic()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $topic = $context->createTopic($tmpFile->getFilename());
//
//        $this->assertInstanceOf(RedisDestination::class, $topic);
//        $this->assertInstanceOf(\SplFileInfo::class, $topic->getFileInfo());
//        $this->assertSame((string) $tmpFile, (string) $topic->getFileInfo());
//
//        $this->assertSame($tmpFile->getFilename(), $topic->getTopicName());
//    }
//
//    public function testShouldAllowCreateTmpQueue()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $queue = $context->createTemporaryQueue();
//
//        $this->assertInstanceOf(RedisDestination::class, $queue);
//        $this->assertInstanceOf(TempFile::class, $queue->getFileInfo());
//        $this->assertNotEmpty($queue->getQueueName());
//    }
//
//    public function testShouldCreateProducer()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $producer = $context->createProducer();
//
//        $this->assertInstanceOf(RedisProducer::class, $producer);
//    }
//
//    public function testShouldThrowIfNotRedisDestinationGivenOnCreateConsumer()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $this->expectException(InvalidDestinationException::class);
//        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Redis\RedisDestination but got Enqueue\Transport\Null\NullQueue.');
//        $consumer = $context->createConsumer(new NullQueue('aQueue'));
//
//        $this->assertInstanceOf(RedisConsumer::class, $consumer);
//    }
//
//    public function testShouldCreateConsumer()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $queue = $context->createQueue($tmpFile->getFilename());
//
//        $context->createConsumer($queue);
//    }
//
//    public function testShouldPropagatePreFetchCountToCreatedConsumer()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $queue = $context->createQueue($tmpFile->getFilename());
//
//        $consumer = $context->createConsumer($queue);
//
//        // guard
//        $this->assertInstanceOf(RedisConsumer::class, $consumer);
//
//        $this->assertAttributeSame(123, 'preFetchCount', $consumer);
//    }
//
//    public function testShouldAllowGetPreFetchCountSetInConstructor()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $this->assertSame(123, $context->getPreFetchCount());
//    }
//
//    public function testShouldAllowGetPreviouslySetPreFetchCount()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $context->setPreFetchCount(456);
//
//        $this->assertSame(456, $context->getPreFetchCount());
//    }
//
//    public function testShouldAllowPurgeMessagesFromQueue()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/foo');
//
//        file_put_contents($tmpFile, 'foo');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $queue = $context->createQueue($tmpFile->getFilename());
//
//        $context->purge($queue);
//
//        $this->assertEmpty(file_get_contents($tmpFile));
//    }
//
//    public function testShouldReleaseAllLocksOnClose()
//    {
//        new TempFile(sys_get_temp_dir().'/foo');
//        new TempFile(sys_get_temp_dir().'/bar');
//
//        $context = new RedisContext($this->createRedisMock());
//
//        $fooQueue = $context->createQueue('foo');
//        $barQueue = $context->createTopic('bar');
//
//        $this->assertAttributeCount(0, 'lockHandlers', $context);
//
//        $context->workWithFile($fooQueue, 'r+', function () {
//        });
//        $context->workWithFile($barQueue, 'r+', function () {
//        });
//        $context->workWithFile($fooQueue, 'c+', function () {
//        });
//        $context->workWithFile($barQueue, 'c+', function () {
//        });
//
//        $this->assertAttributeCount(2, 'lockHandlers', $context);
//
//        $context->close();
//
//        $this->assertAttributeCount(0, 'lockHandlers', $context);
//    }
//
//    public function testShouldCreateFileOnFilesystemIfNotExistOnDeclareDestination()
//    {
//        $tmpFile = new TempFile(sys_get_temp_dir().'/'.uniqid());
//
//        $context = new RedisContext(sys_get_temp_dir(), 1, 0666);
//
//        $queue = $context->createQueue($tmpFile->getFilename());
//
//        $this->assertFileNotExists((string) $tmpFile);
//
//        $context->declareDestination($queue);
//
//        $this->assertFileExists((string) $tmpFile);
//        $this->assertTrue(is_readable($tmpFile));
//        $this->assertTrue(is_writable($tmpFile));
//
//        // do nothing if file already exists
//        $context->declareDestination($queue);
//
//        $this->assertFileExists((string) $tmpFile);
//
//        unlink($tmpFile);
//    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Redis
     */
    private function createRedisMock()
    {
        return $this->createMock(\Redis::class);
    }
}
