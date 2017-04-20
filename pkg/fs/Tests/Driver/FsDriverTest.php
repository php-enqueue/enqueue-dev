<?php

namespace Enqueue\Fs\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Fs\Client\FsDriver;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Enqueue\Fs\FsMessage;
use Enqueue\Psr\PsrProducer;
use Enqueue\Test\ClassExtensionTrait;
use Makasim\File\TempFile;

class FsDriverTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, FsDriver::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new FsDriver(
            $this->createPsrContextMock(),
            new Config('', '', '', '', '', ''),
            $this->createQueueMetaRegistryMock()
        );
    }

    public function testShouldReturnConfigObject()
    {
        $config = new Config('', '', '', '', '', '');

        $driver = new FsDriver($this->createPsrContextMock(), $config, $this->createQueueMetaRegistryMock());

        $this->assertSame($config, $driver->getConfig());
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = new FsDestination(new TempFile(sys_get_temp_dir().'/queue-name'));

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('name')
            ->will($this->returnValue($expectedQueue))
        ;

        $driver = new FsDriver($context, new Config('', '', '', '', '', ''), $this->createQueueMetaRegistryMock());

        $queue = $driver->createQueue('name');

        $this->assertSame($expectedQueue, $queue);
        $this->assertSame('queue-name', $queue->getQueueName());
    }

    public function testShouldConvertTransportMessageToClientMessage()
    {
        $transportMessage = new FsMessage();
        $transportMessage->setBody('body');
        $transportMessage->setHeaders(['hkey' => 'hval']);
        $transportMessage->setProperties(['key' => 'val']);
        $transportMessage->setHeader('content_type', 'ContentType');
        $transportMessage->setMessageId('MessageId');
        $transportMessage->setTimestamp(1000);

        $driver = new FsDriver(
            $this->createPsrContextMock(),
            new Config('', '', '', '', '', ''),
            $this->createQueueMetaRegistryMock()
        );

        $clientMessage = $driver->createClientMessage($transportMessage);

        $this->assertInstanceOf(Message::class, $clientMessage);
        $this->assertSame('body', $clientMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'content_type' => 'ContentType',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
        ], $clientMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $clientMessage->getProperties());
        $this->assertSame('MessageId', $clientMessage->getMessageId());
        $this->assertSame('ContentType', $clientMessage->getContentType());
        $this->assertSame(1000, $clientMessage->getTimestamp());

        $this->assertNull($clientMessage->getExpire());
        $this->assertSame(MessagePriority::NORMAL, $clientMessage->getPriority());
    }

    public function testShouldConvertClientMessageToTransportMessage()
    {
        $clientMessage = new Message();
        $clientMessage->setBody('body');
        $clientMessage->setHeaders(['hkey' => 'hval']);
        $clientMessage->setProperties(['key' => 'val']);
        $clientMessage->setContentType('ContentType');
        $clientMessage->setExpire(123);
        $clientMessage->setPriority(MessagePriority::VERY_HIGH);
        $clientMessage->setMessageId('MessageId');
        $clientMessage->setTimestamp(1000);

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new FsMessage())
        ;

        $driver = new FsDriver(
            $context,
            new Config('', '', '', '', '', ''),
            $this->createQueueMetaRegistryMock()
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertInstanceOf(FsMessage::class, $transportMessage);
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'content_type' => 'ContentType',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
        ], $transportMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $transportMessage->getProperties());
        $this->assertSame('MessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
    }

    public function testShouldSendMessageToRouter()
    {
        $topic = new FsDestination(TempFile::generate());
        $transportMessage = new FsMessage();
        $config = $this->createConfigMock();

        $config
            ->expects($this->once())
            ->method('getRouterTopicName')
            ->willReturn('topicName');

        $config
            ->expects($this->once())
            ->method('createTransportQueueName')
            ->with('topicName')
            ->willReturn('app.topicName');

        $producer = $this->createPsrProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($transportMessage))
        ;
        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createTopic')
            ->with('app.topicName')
            ->willReturn($topic)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new FsDriver(
            $context,
            $config,
            $this->createQueueMetaRegistryMock()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldThrowExceptionIfTopicParameterIsNotSet()
    {
        $driver = new FsDriver(
            $this->createPsrContextMock(),
            new Config('', '', '', '', '', ''),
            $this->createQueueMetaRegistryMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name parameter is required but is not set');

        $driver->sendToRouter(new Message());
    }

    public function testShouldSendMessageToProcessor()
    {
        $queue = new FsDestination(TempFile::generate());
        $transportMessage = new FsMessage();

        $producer = $this->createPsrProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($transportMessage))
        ;
        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($queue)
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage)
        ;

        $driver = new FsDriver(
            $context,
            new Config('', '', '', '', '', ''),
            $this->createQueueMetaRegistryMock()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'queue');

        $driver->sendToProcessor($message);
    }

    public function testShouldThrowExceptionIfProcessorNameParameterIsNotSet()
    {
        $driver = new FsDriver(
            $this->createPsrContextMock(),
            new Config('', '', '', '', '', ''),
            $this->createQueueMetaRegistryMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Processor name parameter is required but is not set');

        $driver->sendToProcessor(new Message());
    }

    public function testShouldThrowExceptionIfProcessorQueueNameParameterIsNotSet()
    {
        $driver = new FsDriver(
            $this->createPsrContextMock(),
            new Config('', '', '', '', '', ''),
            $this->createQueueMetaRegistryMock()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Queue name parameter is required but is not set');

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSetupBroker()
    {
        $routerTopic = new FsDestination(TempFile::generate());
        $routerQueue = new FsDestination(TempFile::generate());

        $processorQueue = new FsDestination(TempFile::generate());

        $context = $this->createPsrContextMock();
        // setup router
        $context
            ->expects($this->at(0))
            ->method('createTopic')
            ->willReturn($routerTopic)
        ;
        $context
            ->expects($this->at(1))
            ->method('createQueue')
            ->willReturn($routerQueue)
        ;
        $context
            ->expects($this->at(2))
            ->method('declareDestination')
            ->with($this->identicalTo($routerTopic))
        ;
        $context
            ->expects($this->at(3))
            ->method('declareDestination')
            ->with($this->identicalTo($routerQueue))
        ;
        // setup processor queue
        $context
            ->expects($this->at(4))
            ->method('createQueue')
            ->willReturn($processorQueue)
        ;
        $context
            ->expects($this->at(5))
            ->method('declareDestination')
            ->with($this->identicalTo($processorQueue))
        ;

        $meta = new QueueMetaRegistry(new Config('', '', '', '', '', ''), [
            'default' => [],
        ], 'default');

        $driver = new FsDriver(
            $context,
            new Config('', '', '', '', '', ''),
            $meta
        );

        $driver->setupBroker();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FsContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(FsContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProducer
     */
    private function createPsrProducerMock()
    {
        return $this->createMock(PsrProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueMetaRegistry
     */
    private function createQueueMetaRegistryMock()
    {
        return $this->createMock(QueueMetaRegistry::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Config
     */
    private function createConfigMock()
    {
        return $this->createMock(Config::class);
    }
}
