<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\BufferedStompClient;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Enqueue\Test\WriteAttributeTrait;
use Stomp\Client;
use Stomp\Network\Connection;
use Stomp\Transport\Frame;

class BufferedStompClientTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;
    use WriteAttributeTrait;

    public function testShouldExtendLibStompClient()
    {
        $this->assertClassExtends(Client::class, BufferedStompClient::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new BufferedStompClient('tcp://localhost:12345');
    }

    public function testCouldGetBufferSizeValue()
    {
        $client = new BufferedStompClient('tcp://localhost:12345', 12345);

        $this->assertSame(12345, $client->getBufferSize());
    }

    public function testShouldCleanBufferOnDisconnect()
    {
        $client = new BufferedStompClient('tcp://localhost:12345', 12345);

        $this->assertObjectHasAttribute('buffer', $client);
        $this->assertObjectHasAttribute('currentBufferSize', $client);

        $this->writeAttribute($client, 'buffer', [1, 2, 3]);
        $this->writeAttribute($client, 'currentBufferSize', 12345);

        $client->disconnect();

        $this->assertSame([], $this->readAttribute($client, 'buffer'));
        $this->assertSame(0, $this->readAttribute($client, 'currentBufferSize'));
    }

    public function testShouldReturnFrameFromTheBufferIfExists()
    {
        $frame = new Frame();

        $client = new BufferedStompClient('tcp://localhost:12345');

        $this->writeAttribute($client, 'buffer', ['subscription-id' => [$frame]]);
        $this->writeAttribute($client, 'currentBufferSize', 100);

        $resultFrame = $client->readMessageFrame('subscription-id', 0);

        $this->assertSame($resultFrame, $frame);
        $this->assertEquals(99, $this->readAttribute($client, 'currentBufferSize'));
    }

    public function testShouldThrowExceptionIfFrameIsNotMessageFrame()
    {
        $frame = new Frame('NOT-MESSAGE-FRAME');

        $connection = $this->createStompConnectionMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->willReturn($frame)
        ;

        $client = new BufferedStompClient($connection);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected frame was received: "NOT-MESSAGE-FRAME"');

        $client->readMessageFrame('subscription-id', 0);
    }

    public function testShouldThrowExceptionIfFrameHasNoSubscriptionHeader()
    {
        $frame = new Frame('MESSAGE');

        $connection = $this->createStompConnectionMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->willReturn($frame)
        ;

        $client = new BufferedStompClient($connection);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Got message frame with missing subscription header');

        $client->readMessageFrame('subscription-id', 0);
    }

    public function testShouldReturnReceivedFrameIfSubscriptionIdIsEqual()
    {
        $frame = new Frame('MESSAGE');
        $frame['subscription'] = 'subscription-id';

        $connection = $this->createStompConnectionMock();
        $connection
            ->expects($this->once())
            ->method('readFrame')
            ->willReturn($frame)
        ;

        $client = new BufferedStompClient($connection);

        $resultFrame = $client->readMessageFrame('subscription-id', 0);

        $this->assertSame($resultFrame, $frame);
    }

    public function testShouldAddFrameToBufferIfSubscriptionIdIsNotEqual()
    {
        $frame = new Frame('MESSAGE');
        $frame['subscription'] = 'subscription-id-2';

        $connection = $this->createStompConnectionMock();
        $connection
            ->expects($this->at(1))
            ->method('readFrame')
            ->willReturn($frame)
        ;
        $connection
            ->expects($this->at(2))
            ->method('readFrame')
            ->willReturn(false)
        ;

        $client = new BufferedStompClient($connection);

        $returnValue = $client->readMessageFrame('subscription-id-1', 0);

        $buffer = $this->readAttribute($client, 'buffer');
        $bufferedFrame = $buffer['subscription-id-2'][0];

        $this->assertSame($frame, $bufferedFrame);
        $this->assertNull($returnValue);
    }

    public function testShouldAddFirstFrameToBufferIfSubscriptionNotEqualAndReturnSecondFrameIfSubscriptionIsEqual()
    {
        $frame1 = new Frame('MESSAGE');
        $frame1['subscription'] = 'subscription-id-1';

        $frame2 = new Frame('MESSAGE');
        $frame2['subscription'] = 'subscription-id-2';

        $connection = $this->createStompConnectionMock();
        $connection
            ->expects($this->at(1))
            ->method('readFrame')
            ->willReturn($frame1)
        ;
        $connection
            ->expects($this->at(3))
            ->method('readFrame')
            ->willReturn($frame2)
        ;

        $client = new BufferedStompClient($connection);

        $returnFrame = $client->readMessageFrame('subscription-id-2', 1);

        $buffer = $this->readAttribute($client, 'buffer');
        $bufferedFrame = $buffer['subscription-id-1'][0];

        $this->assertSame($frame1, $bufferedFrame);
        $this->assertSame($frame2, $returnFrame);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Connection
     */
    private function createStompConnectionMock()
    {
        return $this->createMock(Connection::class);
    }
}
