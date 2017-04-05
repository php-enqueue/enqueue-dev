<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\MessageProducer;
use Enqueue\Client\MessageProducerInterface;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullQueue;

class MessageProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        self::assertClassImplements(MessageProducerInterface::class, MessageProducer::class);
    }

    public function testCouldBeConstructedWithDriverAsFirstArgument()
    {
        new MessageProducer($this->createDriverStub());
    }

    public function testShouldSendMessageToRouter()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);

        $expectedProperties = [
            'enqueue.topic_name' => 'topic',
        ];

        self::assertEquals($expectedProperties, $message->getProperties());
    }

    public function testShouldSendMessageWithNormalPriorityByDefault()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);

        self::assertSame(MessagePriority::NORMAL, $message->getPriority());
    }

    public function testShouldSendMessageWithCustomPriority()
    {
        $message = new Message();
        $message->setPriority(MessagePriority::HIGH);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);

        self::assertSame(MessagePriority::HIGH, $message->getPriority());
    }

    public function testShouldSendMessageWithGeneratedMessageId()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);

        self::assertNotEmpty($message->getMessageId());
    }

    public function testShouldSendMessageWithCustomMessageId()
    {
        $message = new Message();
        $message->setMessageId('theCustomMessageId');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);

        self::assertSame('theCustomMessageId', $message->getMessageId());
    }

    public function testShouldSendMessageWithGeneratedTimestamp()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);

        self::assertNotEmpty($message->getTimestamp());
    }

    public function testShouldSendMessageWithCustomTimestamp()
    {
        $message = new Message();
        $message->setTimestamp('theCustomTimestamp');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->with(self::identicalTo($message))
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);

        self::assertSame('theCustomTimestamp', $message->getTimestamp());
    }

    public function testShouldSendStringAsPlainText()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('theStringMessage', $message->getBody());
                self::assertSame('text/plain', $message->getContentType());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', 'theStringMessage');
    }

    public function testShouldSendArrayAsJsonString()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('{"foo":"fooVal"}', $message->getBody());
                self::assertSame('application/json', $message->getContentType());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', ['foo' => 'fooVal']);
    }

    public function testShouldConvertMessageArrayBodyJsonString()
    {
        $message = new Message();
        $message->setBody(['foo' => 'fooVal']);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('{"foo":"fooVal"}', $message->getBody());
                self::assertSame('application/json', $message->getContentType());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);
    }

    public function testSendShouldForceScalarsToStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertEquals('text/plain', $message->getContentType());

                self::assertInternalType('string', $message->getBody());
                self::assertEquals('12345', $message->getBody());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send($queue, 12345);
    }

    public function testSendShouldForceMessageScalarsBodyToStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $message = new Message();
        $message->setBody(12345);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertEquals('text/plain', $message->getContentType());

                self::assertInternalType('string', $message->getBody());
                self::assertEquals('12345', $message->getBody());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send($queue, $message);
    }

    public function testSendShouldForceNullToEmptyStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertEquals('text/plain', $message->getContentType());

                self::assertInternalType('string', $message->getBody());
                self::assertEquals('', $message->getBody());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send($queue, null);
    }

    public function testSendShouldForceNullBodyToEmptyStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $message = new Message();
        $message->setBody(null);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertEquals('text/plain', $message->getContentType());

                self::assertInternalType('string', $message->getBody());
                self::assertEquals('', $message->getBody());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send($queue, $message);
    }

    public function testShouldThrowExceptionIfBodyIsObjectOnSend()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new MessageProducer($driver);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The message\'s body must be either null, scalar, array or object (implements \JsonSerializable). Got: stdClass');

        $producer->send('topic', new \stdClass());
    }

    public function testShouldThrowExceptionIfBodyIsArrayWithObjectsInsideOnSend()
    {
        $queue = new NullQueue('queue');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new MessageProducer($driver);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message\'s body must be an array of scalars. Found not scalar in the array: stdClass');

        $producer->send($queue, ['foo' => new \stdClass()]);
    }

    public function testShouldThrowExceptionIfBodyIsArrayWithObjectsInSubArraysInsideOnSend()
    {
        $queue = new NullQueue('queue');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new MessageProducer($driver);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message\'s body must be an array of scalars. Found not scalar in the array: stdClass');

        $producer->send($queue, ['foo' => ['bar' => new \stdClass()]]);
    }

    public function testShouldSendJsonSerializableObjectAsJsonStringToMessageBus()
    {
        $object = new JsonSerializableObject();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('{"foo":"fooVal"}', $message->getBody());
                self::assertSame('application/json', $message->getContentType());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $object);
    }

    public function testShouldSendMessageJsonSerializableBodyAsJsonStringToMessageBus()
    {
        $object = new JsonSerializableObject();

        $message = new Message();
        $message->setBody($object);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToRouter')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('{"foo":"fooVal"}', $message->getBody());
                self::assertSame('application/json', $message->getContentType());
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);
    }

    public function testThrowIfTryToSendMessageToMessageBusWithProcessorNamePropertySet()
    {
        $object = new JsonSerializableObject();

        $message = new Message();
        $message->setBody($object);
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'aProcessor');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new MessageProducer($driver);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The enqueue.processor_name property must not be set for messages that are sent to message bus.');
        $producer->send('topic', $message);
    }

    public function testThrowIfTryToSendMessageToMessageBusWithProcessorQueueNamePropertySet()
    {
        $object = new JsonSerializableObject();

        $message = new Message();
        $message->setBody($object);
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aProcessorQueue');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new MessageProducer($driver);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The enqueue.processor_queue_name property must not be set for messages that are sent to message bus.');
        $producer->send('topic', $message);
    }

    public function testThrowIfNotApplicationJsonContentTypeSetWithJsonSerializableBody()
    {
        $object = new JsonSerializableObject();

        $message = new Message();
        $message->setBody($object);
        $message->setContentType('foo/bar');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content type "application/json" only allowed when body is array');

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);
    }

    public function testShouldSendMessageToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('aBody', $message->getBody());
                self::assertSame('a_router_processor_name', $message->getProperty(Config::PARAMETER_PROCESSOR_NAME));
                self::assertSame('a_router_queue', $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME));
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);
    }

    public function testShouldSendToCustomMessageToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'aCustomProcessor');
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aCustomProcessorQueue');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('aBody', $message->getBody());
                self::assertSame('aCustomProcessor', $message->getProperty(Config::PARAMETER_PROCESSOR_NAME));
                self::assertSame('aCustomProcessorQueue', $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME));
            })
        ;

        $producer = new MessageProducer($driver);
        $producer->send('topic', $message);
    }

    public function testThrowIfUnSupportedScopeGivenOnSend()
    {
        $message = new Message();
        $message->setScope('iDontKnowScope');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new MessageProducer($driver);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The message scope "iDontKnowScope" is not supported.');
        $producer->send('topic', $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverStub()
    {
        $config = new Config(
            'a_prefix',
            'an_app',
            'a_router_topic',
            'a_router_queue',
            'a_default_processor_queue',
            'a_router_processor_name'
        );

        $driverMock = $this->createMock(DriverInterface::class);
        $driverMock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        return $driverMock;
    }
}

class JsonSerializableObject implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'fooVal'];
    }
}
