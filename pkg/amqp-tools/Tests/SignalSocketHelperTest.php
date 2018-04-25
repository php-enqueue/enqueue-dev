<?php

namespace Enqueue\AmqpTools\Tests;

use Enqueue\AmqpTools\SignalSocketHelper;
use PHPUnit\Framework\TestCase;

class SignalSocketHelperTest extends TestCase
{
    /**
     * @var SignalSocketHelper
     */
    private $signalHelper;

    private $backupSigTermHandler;

    private $backupSigIntHandler;

    public function setUp()
    {
        parent::setUp();

        if (false == function_exists('pcntl_signal_get_handler')) {
            $this->markTestSkipped('PHP 7.1 and higher');
        }

        $this->backupSigTermHandler = pcntl_signal_get_handler(SIGTERM);
        $this->backupSigIntHandler = pcntl_signal_get_handler(SIGINT);

        pcntl_signal(SIGTERM, SIG_DFL);
        pcntl_signal(SIGINT, SIG_DFL);

        $this->signalHelper = new SignalSocketHelper();
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->signalHelper) {
            $this->signalHelper->afterSocket();
        }

        if ($this->backupSigTermHandler) {
            pcntl_signal(SIGTERM, $this->backupSigTermHandler);
        }

        if ($this->backupSigIntHandler) {
            pcntl_signal(SIGINT, $this->backupSigIntHandler);
        }
    }

    public function testShouldReturnFalseByDefault()
    {
        $this->assertFalse($this->signalHelper->wasThereSignal());
    }

    public function testShouldRegisterHandlerOnBeforeSocket()
    {
        $this->signalHelper->beforeSocket();

        $this->assertAttributeSame(false, 'wasThereSignal', $this->signalHelper);
        $this->assertAttributeSame([], 'handlers', $this->signalHelper);
    }

    public function testShouldRegisterHandlerOnBeforeSocketAndBackupCurrentOne()
    {
        $handler = function () {};

        pcntl_signal(SIGTERM, $handler);

        $this->signalHelper->beforeSocket();

        $this->assertAttributeSame(false, 'wasThereSignal', $this->signalHelper);

        $handlers = $this->readAttribute($this->signalHelper, 'handlers');

        $this->assertInternalType('array', $handlers);
        $this->assertArrayHasKey(SIGTERM, $handlers);
        $this->assertSame($handler, $handlers[SIGTERM]);
    }

    public function testRestoreDefaultPropertiesOnAfterSocket()
    {
        $this->signalHelper->beforeSocket();
        $this->signalHelper->afterSocket();

        $this->assertAttributeSame(null, 'wasThereSignal', $this->signalHelper);
        $this->assertAttributeSame([], 'handlers', $this->signalHelper);
    }

    public function testRestorePreviousHandlerOnAfterSocket()
    {
        $handler = function () {};

        pcntl_signal(SIGTERM, $handler);

        $this->signalHelper->beforeSocket();
        $this->signalHelper->afterSocket();

        $this->assertSame($handler, pcntl_signal_get_handler(SIGTERM));
    }

    public function testThrowsIfBeforeSocketCalledSecondTime()
    {
        $this->signalHelper->beforeSocket();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The wasThereSignal property should be null but it is not. The afterSocket method might not have been called.');
        $this->signalHelper->beforeSocket();
    }

    public function testShouldReturnTrueOnWasThereSignal()
    {
        $this->signalHelper->beforeSocket();

        posix_kill(getmypid(), SIGINT);
        pcntl_signal_dispatch();

        $this->assertTrue($this->signalHelper->wasThereSignal());

        $this->signalHelper->afterSocket();
    }
}
