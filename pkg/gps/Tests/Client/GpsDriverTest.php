<?php

namespace Enqueue\Gps\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsMessage;
use Enqueue\Gps\GpsProducer;
use Enqueue\Gps\GpsQueue;
use Enqueue\Gps\GpsTopic;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class GpsDriverTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, GpsDriver::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new GpsDriver(
            $this->createGpsContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );
    }

    public function testShouldReturnConfigObject()
    {
        $config = $this->createDummyConfig();

        $driver = new GpsDriver($this->createGpsContextMock(), $config, $this->createDummyQueueMetaRegistry());

        $this->assertSame($config, $driver->getConfig());
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = new GpsQueue('aName');

        $context = $this->createGpsContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aprefix.afooqueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new GpsDriver($context, $this->createDummyConfig(), $this->createDummyQueueMetaRegistry());

        $queue = $driver->createQueue('aFooQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldCreateAndReturnQueueInstanceWithHardcodedTransportName()
    {
        $expectedQueue = new GpsQueue('aName');

        $context = $this->createGpsContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('aBarQueue')
            ->willReturn($expectedQueue)
        ;

        $driver = new GpsDriver($context, $this->createDummyConfig(), $this->createDummyQueueMetaRegistry());

        $queue = $driver->createQueue('aBarQueue');

        $this->assertSame($expectedQueue, $queue);
    }

    public function testShouldConvertTransportMessageToClientMessage()
    {
        $transportMessage = new GpsMessage();
        $transportMessage->setBody('body');
        $transportMessage->setHeaders(['hkey' => 'hval']);
        $transportMessage->setProperties(['key' => 'val']);
        $transportMessage->setMessageId('MessageId');
        $transportMessage->setTimestamp(1000);
        $transportMessage->setReplyTo('theReplyTo');
        $transportMessage->setCorrelationId('theCorrelationId');

        $driver = new GpsDriver(
            $this->createGpsContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $clientMessage = $driver->createClientMessage($transportMessage);

        $this->assertInstanceOf(Message::class, $clientMessage);
        $this->assertSame('body', $clientMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $clientMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $clientMessage->getProperties());
        $this->assertSame('MessageId', $clientMessage->getMessageId());
        $this->assertNull($clientMessage->getExpire());
        $this->assertNull($clientMessage->getContentType());
        $this->assertSame(1000, $clientMessage->getTimestamp());
        $this->assertSame('theReplyTo', $clientMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $clientMessage->getCorrelationId());
    }

    public function testShouldConvertClientMessageToTransportMessage()
    {
        $clientMessage = new Message();
        $clientMessage->setBody('body');
        $clientMessage->setHeaders(['hkey' => 'hval']);
        $clientMessage->setProperties(['key' => 'val']);
        $clientMessage->setContentType('ContentType');
        $clientMessage->setExpire(123);
        $clientMessage->setMessageId('MessageId');
        $clientMessage->setTimestamp(1000);
        $clientMessage->setReplyTo('theReplyTo');
        $clientMessage->setCorrelationId('theCorrelationId');

        $context = $this->createGpsContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new GpsMessage())
        ;

        $driver = new GpsDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertInstanceOf(GpsMessage::class, $transportMessage);
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
            'reply_to' => 'theReplyTo',
            'correlation_id' => 'theCorrelationId',
        ], $transportMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $transportMessage->getProperties());
        $this->assertSame('MessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
        $this->assertSame('theReplyTo', $transportMessage->getReplyTo());
        $this->assertSame('theCorrelationId', $transportMessage->getCorrelationId());
    }

    public function testShouldSendMessageToRouter()
    {
        $topic = new GpsTopic('');
        $transportMessage = new GpsMessage();

        $producer = $this->createGpsProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($transportMessage))
        ;
        $context = $this->createGpsContextMock();
        $context
            ->expects($this->once())
            ->method('createTopic')
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

        $driver = new GpsDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldThrowExceptionIfTopicParameterIsNotSet()
    {
        $driver = new GpsDriver(
            $this->createGpsContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name parameter is required but is not set');

        $driver->sendToRouter(new Message());
    }

    public function testShouldSendMessageToProcessor()
    {
        $topic = new GpsTopic('');
        $transportMessage = new GpsMessage();

        $producer = $this->createGpsProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($transportMessage))
        ;
        $context = $this->createGpsContextMock();
        $context
            ->expects($this->once())
            ->method('createTopic')
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

        $driver = new GpsDriver(
            $context,
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aFooQueue');

        $driver->sendToProcessor($message);
    }

    public function testShouldThrowExceptionIfProcessorNameParameterIsNotSet()
    {
        $driver = new GpsDriver(
            $this->createGpsContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Processor name parameter is required but is not set');

        $driver->sendToProcessor(new Message());
    }

    public function testShouldThrowExceptionIfProcessorQueueNameParameterIsNotSet()
    {
        $driver = new GpsDriver(
            $this->createGpsContextMock(),
            $this->createDummyConfig(),
            $this->createDummyQueueMetaRegistry()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Queue name parameter is required but is not set');

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSetupBroker()
    {
        $routerTopic = new GpsTopic('');
        $routerQueue = new GpsQueue('');

        $processorTopic = new GpsTopic('');
        $processorQueue = new GpsQueue('');

        $context = $this->createGpsContextMock();
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
            ->method('subscribe')
            ->with($this->identicalTo($routerTopic), $this->identicalTo($routerQueue))
        ;
        // setup processor queue
        $context
            ->expects($this->at(3))
            ->method('createTopic')
            ->willReturn($processorTopic)
        ;
        $context
            ->expects($this->at(4))
            ->method('createQueue')
            ->willReturn($processorQueue)
        ;
        $context
            ->expects($this->at(5))
            ->method('subscribe')
            ->with($this->identicalTo($processorTopic), $this->identicalTo($processorQueue))
        ;

        $meta = new QueueMetaRegistry($this->createDummyConfig(), [
            'default' => [],
        ]);

        $driver = new GpsDriver(
            $context,
            $this->createDummyConfig(),
            $meta
        );

        $driver->setupBroker();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GpsContext
     */
    private function createGpsContextMock()
    {
        return $this->createMock(GpsContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GpsProducer
     */
    private function createGpsProducerMock()
    {
        return $this->createMock(GpsProducer::class);
    }

    /**
     * @return QueueMetaRegistry
     */
    private function createDummyQueueMetaRegistry()
    {
        $registry = new QueueMetaRegistry($this->createDummyConfig(), []);
        $registry->add('default');
        $registry->add('aFooQueue');
        $registry->add('aBarQueue', 'aBarQueue');

        return $registry;
    }

    /**
     * @return Config
     */
    private function createDummyConfig()
    {
        return Config::create('aPrefix');
    }
}
