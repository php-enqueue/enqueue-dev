<?php

namespace Enqueue\Dbal\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Dbal\Client\DbalDriver;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;
use Enqueue\Psr\PsrProducer;
use Enqueue\Test\ClassExtensionTrait;

class DbalDriverTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsDriverInterface()
    {
        $this->assertClassImplements(DriverInterface::class, DbalDriver::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalDriver(
            $this->createPsrContextMock(),
            Config::create()
        );
    }

    public function testShouldReturnConfigObject()
    {
        $config = Config::create();

        $driver = new DbalDriver($this->createPsrContextMock(), $config);

        $this->assertSame($config, $driver->getConfig());
    }

    public function testShouldCreateAndReturnQueueInstance()
    {
        $expectedQueue = new DbalDestination('queue-name');

        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('name')
            ->will($this->returnValue($expectedQueue))
        ;

        $driver = new DbalDriver($context, Config::create());

        $queue = $driver->createQueue('name');

        $this->assertSame($expectedQueue, $queue);
        $this->assertSame('queue-name', $queue->getQueueName());
    }

    public function testShouldConvertTransportMessageToClientMessage()
    {
        $transportMessage = new DbalMessage();
        $transportMessage->setBody('body');
        $transportMessage->setHeaders(['hkey' => 'hval']);
        $transportMessage->setProperties(['key' => 'val']);
        $transportMessage->setHeader('content_type', 'ContentType');
        $transportMessage->setMessageId('MessageId');
        $transportMessage->setTimestamp(1000);
        $transportMessage->setDelay(12345);

        $driver = new DbalDriver(
            $this->createPsrContextMock(),
            Config::create()
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
        $this->assertSame(12345, $clientMessage->getDelay());

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
            ->willReturn(new DbalMessage())
        ;

        $driver = new DbalDriver(
            $context,
            Config::create()
        );

        $transportMessage = $driver->createTransportMessage($clientMessage);

        $this->assertInstanceOf(DbalMessage::class, $transportMessage);
        $this->assertSame('body', $transportMessage->getBody());
        $this->assertSame([
            'hkey' => 'hval',
            'content_type' => 'ContentType',
            'message_id' => 'MessageId',
            'timestamp' => 1000,
            'reply_to' => null,
            'correlation_id' => null
        ], $transportMessage->getHeaders());
        $this->assertSame([
            'key' => 'val',
        ], $transportMessage->getProperties());
        $this->assertSame('MessageId', $transportMessage->getMessageId());
        $this->assertSame(1000, $transportMessage->getTimestamp());
    }

    public function testShouldSendMessageToRouter()
    {
        $topic = new DbalDestination('queue-name');
        $transportMessage = new DbalMessage();
        $config = $this->createConfigMock();

        $config
            ->expects($this->once())
            ->method('getRouterQueueName')
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
            ->method('createQueue')
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

        $driver = new DbalDriver(
            $context,
            $config
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'topic');

        $driver->sendToRouter($message);
    }

    public function testShouldThrowExceptionIfTopicParameterIsNotSet()
    {
        $driver = new DbalDriver(
            $this->createPsrContextMock(),
            Config::create()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Topic name parameter is required but is not set');

        $driver->sendToRouter(new Message());
    }

    public function testShouldSendMessageToProcessor()
    {
        $queue = new DbalDestination('queue-name');
        $transportMessage = new DbalMessage();

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

        $driver = new DbalDriver(
            $context,
            Config::create()
        );

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'queue');

        $driver->sendToProcessor($message);
    }

    public function testShouldThrowExceptionIfProcessorNameParameterIsNotSet()
    {
        $driver = new DbalDriver(
            $this->createPsrContextMock(),
            Config::create()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Processor name parameter is required but is not set');

        $driver->sendToProcessor(new Message());
    }

    public function testShouldThrowExceptionIfProcessorQueueNameParameterIsNotSet()
    {
        $driver = new DbalDriver(
            $this->createPsrContextMock(),
            Config::create()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Queue name parameter is required but is not set');

        $message = new Message();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'processor');

        $driver->sendToProcessor($message);
    }

    public function testShouldSetupBroker()
    {
        $context = $this->createPsrContextMock();
        $context
            ->expects($this->once())
            ->method('getTableName')
        ;
        $context
            ->expects($this->once())
            ->method('createDataBaseTable')
        ;

        $driver = new DbalDriver(
            $context,
            Config::create()
        );

        $driver->setupBroker();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(DbalContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrProducer
     */
    private function createPsrProducerMock()
    {
        return $this->createMock(PsrProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Config
     */
    private function createConfigMock()
    {
        return $this->createMock(Config::class);
    }
}
