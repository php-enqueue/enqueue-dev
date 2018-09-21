<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\DriverPreSend;
use Enqueue\Client\ExtensionInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\PostSend;
use Enqueue\Client\PreSend;
use Enqueue\Client\Producer;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Tests\Mocks\CustomPrepareBodyClientExtension;
use PHPUnit\Framework\TestCase;

class ProducerSendCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldSendCommandToProcessor()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', $message);

        $expectedProperties = [
            'enqueue.command_name' => 'command',
        ];

        self::assertEquals($expectedProperties, $message->getProperties());
    }

    public function testShouldSendCommandWithReply()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;

        $expectedPromiseMock = $this->createMock(Promise::class);

        $rpcFactoryMock = $this->createRpcFactoryMock();
        $rpcFactoryMock
            ->expects($this->once())
            ->method('createReplyTo')
            ->willReturn('theReplyQueue')
        ;
        $rpcFactoryMock
            ->expects($this->once())
            ->method('createPromise')
            ->with(
                'theReplyQueue',
                $this->logicalNot($this->isEmpty()),
                60000
            )
            ->willReturn($expectedPromiseMock)
        ;

        $producer = new Producer($driver, $rpcFactoryMock);
        $actualPromise = $producer->sendCommand('command', $message, true);

        $this->assertSame($expectedPromiseMock, $actualPromise);

        self::assertEquals('theReplyQueue', $message->getReplyTo());
        self::assertNotEmpty($message->getCorrelationId());
    }

    public function testShouldSendCommandWithReplyAndCustomReplyQueueAndCorrelationId()
    {
        $message = new Message();
        $message->setReplyTo('theCustomReplyQueue');
        $message->setCorrelationId('theCustomCorrelationId');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;
        $driver
            ->expects($this->never())
            ->method('sendToRouter')
        ;

        $expectedPromiseMock = $this->createMock(Promise::class);

        $rpcFactoryMock = $this->createRpcFactoryMock();
        $rpcFactoryMock
            ->expects($this->never())
            ->method('createReplyTo')
        ;
        $rpcFactoryMock
            ->expects($this->once())
            ->method('createPromise')
            ->with(
                'theCustomReplyQueue',
                'theCustomCorrelationId',
                60000
            )
            ->willReturn($expectedPromiseMock)
        ;

        $producer = new Producer($driver, $rpcFactoryMock);
        $actualPromise = $producer->sendCommand('command', $message, true);

        $this->assertSame($expectedPromiseMock, $actualPromise);

        self::assertEquals('theCustomReplyQueue', $message->getReplyTo());
        self::assertSame('theCustomCorrelationId', $message->getCorrelationId());
    }

    public function testShouldOverwriteExpectedMessageProperties()
    {
        $message = new Message();
        $message->setProperty(Config::PARAMETER_COMMAND_NAME, 'commandShouldBeOverwritten');
        $message->setScope('scopeShouldBeOverwritten');

        $driver = $this->createDriverStub();

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('expectedCommand', $message);

        $expectedProperties = [
            'enqueue.command_name' => 'expectedCommand',
        ];

        self::assertEquals($expectedProperties, $message->getProperties());
        self::assertSame(Message::SCOPE_APP, $message->getScope());
    }

    public function testShouldSendCommandWithoutPriorityByDefault()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', $message);

        self::assertNull($message->getPriority());
    }

    public function testShouldSendCommandWithCustomPriority()
    {
        $message = new Message();
        $message->setPriority(MessagePriority::HIGH);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', $message);

        self::assertSame(MessagePriority::HIGH, $message->getPriority());
    }

    public function testShouldSendCommandWithGeneratedMessageId()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', $message);

        self::assertNotEmpty($message->getMessageId());
    }

    public function testShouldSendCommandWithCustomMessageId()
    {
        $message = new Message();
        $message->setMessageId('theCustomMessageId');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', $message);

        self::assertSame('theCustomMessageId', $message->getMessageId());
    }

    public function testShouldSendCommandWithGeneratedTimestamp()
    {
        $message = new Message();

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', $message);

        self::assertNotEmpty($message->getTimestamp());
    }

    public function testShouldSendCommandWithCustomTimestamp()
    {
        $message = new Message();
        $message->setTimestamp('theCustomTimestamp');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->with(self::identicalTo($message))
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', $message);

        self::assertSame('theCustomTimestamp', $message->getTimestamp());
    }

    public function testShouldSerializeMessageToJsonByDefault()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) {
                $this->assertSame('{"foo":"fooVal"}', $message->getBody());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', ['foo' => 'fooVal']);
    }

    public function testShouldSerializeMessageByCustomExtension()
    {
        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) {
                $this->assertSame('theCommandBodySerializedByCustomExtension', $message->getBody());
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock(), new CustomPrepareBodyClientExtension());
        $producer->sendCommand('command', ['foo' => 'fooVal']);
    }

    public function testShouldSendCommandToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
            ->willReturnCallback(function (Message $message) {
                self::assertSame('aBody', $message->getBody());
                self::assertNull($message->getProperty(Config::PARAMETER_PROCESSOR_NAME));
                self::assertSame('command', $message->getProperty(Config::PARAMETER_COMMAND_NAME));
            })
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());
        $producer->sendCommand('command', $message);
    }

    public function testThrowWhenProcessorNamePropertySetToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'aCustomProcessor');

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->never())
            ->method('sendToProcessor')
        ;

        $producer = new Producer($driver, $this->createRpcFactoryMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The enqueue.processor_name property must not be set.');
        $producer->sendCommand('command', $message);
    }

    public function testShouldCallPreSendCommandExtensionMethodWhenSendToBus()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_MESSAGE_BUS);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects($this->at(0))
            ->method('onPreSendCommand')
            ->willReturnCallback(function (PreSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('command', $context->getCommand());

                $this->assertEquals($message, $context->getOriginalMessage());
                $this->assertNotSame($message, $context->getOriginalMessage());
            });

        $extension
            ->expects($this->never())
            ->method('onPreSendEvent')
        ;

        $producer->sendCommand('command', $message);
    }

    public function testShouldCallPreSendCommandExtensionMethodWhenSendToApplicationRouter()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects($this->at(0))
            ->method('onPreSendCommand')
            ->willReturnCallback(function (PreSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('command', $context->getCommand());

                $this->assertEquals($message, $context->getOriginalMessage());
                $this->assertNotSame($message, $context->getOriginalMessage());
            });

        $extension
            ->expects($this->never())
            ->method('onPreSendEvent')
        ;

        $producer->sendCommand('command', $message);
    }

    public function testShouldCallPreDriverSendExtensionMethod()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects($this->at(0))
            ->method('onDriverPreSend')
            ->willReturnCallback(function (DriverPreSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('command', $context->getCommand());

                $this->assertTrue($context->isEvent());
            });

        $producer->sendCommand('command', $message);
    }

    public function testShouldCallPostSendExtensionMethod()
    {
        $message = new Message();
        $message->setBody('aBody');
        $message->setScope(Message::SCOPE_APP);

        $driver = $this->createDriverStub();
        $driver
            ->expects($this->once())
            ->method('sendToProcessor')
        ;

        $extension = $this->createMock(ExtensionInterface::class);

        $producer = new Producer($driver, $this->createRpcFactoryMock(), $extension);

        $extension
            ->expects($this->at(0))
            ->method('onDriverPreSend')
            ->willReturnCallback(function (PostSend $context) use ($message, $producer, $driver) {
                $this->assertSame($message, $context->getMessage());
                $this->assertSame($producer, $context->getProducer());
                $this->assertSame($driver, $context->getDriver());
                $this->assertSame('command', $context->getCommand());

                $this->assertFalse($context->isEvent());
            });

        $producer->sendCommand('command', $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createRpcFactoryMock(): RpcFactory
    {
        return $this->createMock(RpcFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createDriverStub(): DriverInterface
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
