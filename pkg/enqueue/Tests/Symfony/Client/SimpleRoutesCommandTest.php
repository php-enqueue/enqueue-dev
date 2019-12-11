<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\RouteCollection;
use Enqueue\Symfony\Client\RoutesCommand;
use Enqueue\Symfony\Client\SimpleRoutesCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SimpleRoutesCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfRoutesCommand()
    {
        $this->assertClassExtends(RoutesCommand::class, SimpleRoutesCommand::class);
    }

    public function testShouldNotBeFinal()
    {
        $this->assertClassNotFinal(SimpleRoutesCommand::class);
    }

    public function testCouldBeConstructedWithConfigAndRouteCollectionAsArguments()
    {
        new SimpleRoutesCommand($this->createDriverMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new SimpleRoutesCommand($this->createDriverMock());

        $this->assertEquals('enqueue:routes', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new SimpleRoutesCommand($this->createDriverMock());

        $this->assertEquals(['debug:enqueue:routes'], $command->getAliases());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new SimpleRoutesCommand($this->createDriverMock());

        $options = $command->getDefinition()->getOptions();
        $this->assertCount(2, $options);

        $this->assertArrayHasKey('show-route-options', $options);
        $this->assertArrayHasKey('client', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new SimpleRoutesCommand($this->createDriverMock());

        $arguments = $command->getDefinition()->getArguments();
        $this->assertCount(0, $arguments);
    }

    public function testShouldOutputEmptyRouteCollection()
    {
        $routeCollection = new RouteCollection([]);

        $command = new SimpleRoutesCommand($this->createDriverStub(Config::create(), $routeCollection));

        $tester = new CommandTester($command);

        $tester->execute([]);

        $expectedOutput = <<<'OUTPUT'
Found 0 routes


OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createDriverMock(): DriverInterface
    {
        return $this->createMock(DriverInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createDriverStub(Config $config, RouteCollection $routeCollection): DriverInterface
    {
        $driverMock = $this->createDriverMock();
        $driverMock
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($config)
        ;

        $driverMock
            ->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($routeCollection)
        ;

        return $driverMock;
    }

    private function assertCommandOutput(string $expected, CommandTester $tester): void
    {
        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($expected, $tester->getDisplay());
    }
}
