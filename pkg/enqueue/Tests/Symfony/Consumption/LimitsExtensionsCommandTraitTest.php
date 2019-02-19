<?php

namespace Enqueue\Tests\Symfony\Consumption;

use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\NicenessExtension;
use Enqueue\Tests\Symfony\Consumption\Mock\LimitsExtensionsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LimitsExtensionsCommandTraitTest extends TestCase
{
    public function testShouldAddExtensionsOptions()
    {
        $trait = new LimitsExtensionsCommand('name');

        $options = $trait->getDefinition()->getOptions();

        $this->assertCount(4, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
        $this->assertArrayHasKey('niceness', $options);
    }

    public function testShouldAddMessageLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--message-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LimitConsumedMessagesExtension::class, $result[0]);
    }

    public function testShouldAddTimeLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--time-limit' => '+5',
        ]);

        $result = $command->getExtensions();

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LimitConsumptionTimeExtension::class, $result[0]);
    }

    public function testShouldThrowExceptionIfTimeLimitExpressionIsNotValid()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to parse time string (time is not valid) at position');

        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--time-limit' => 'time is not valid',
        ]);

        $command->getExtensions();
    }

    public function testShouldAddMemoryLimitExtension()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--memory-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(1, $result);

        $this->assertInstanceOf(LimitConsumerMemoryExtension::class, $result[0]);
    }

    public function testShouldAddThreeLimitExtensions()
    {
        $command = new LimitsExtensionsCommand('name');

        $tester = new CommandTester($command);
        $tester->execute([
            '--time-limit' => '+5',
            '--memory-limit' => 5,
            '--message-limit' => 5,
        ]);

        $result = $command->getExtensions();

        $this->assertCount(3, $result);

        $this->assertInstanceOf(LimitConsumedMessagesExtension::class, $result[0]);
        $this->assertInstanceOf(LimitConsumptionTimeExtension::class, $result[1]);
        $this->assertInstanceOf(LimitConsumerMemoryExtension::class, $result[2]);
    }

    /**
     * @param mixed $inputValue
     * @param bool  $enabled
     * @dataProvider provideNicenessValues
     */
    public function testShouldAddNicenessExtension($inputValue, bool $enabled)
    {
        $command = new LimitsExtensionsCommand('name');
        $tester = new CommandTester($command);
        $tester->execute([
            '--niceness' => $inputValue,
        ]);

        $result = $command->getExtensions();

        if ($enabled) {
            $this->assertCount(1, $result);
            $this->assertInstanceOf(NicenessExtension::class, $result[0]);
        } else {
            $this->assertEmpty($result);
        }
    }

    public function provideNicenessValues(): \Generator
    {
        yield [1, true];
        yield ['1', true];
        yield [-1.0, true];
        yield ['100', true];
        yield ['', false];
        yield ['0', false];
        yield [0.0, false];
    }
}
