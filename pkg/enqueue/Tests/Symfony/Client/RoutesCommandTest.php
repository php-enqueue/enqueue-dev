<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Container\Container;
use Enqueue\Symfony\Client\RoutesCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RoutesCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, RoutesCommand::class);
    }

    public function testShouldNotBeFinal()
    {
        $this->assertClassNotFinal(RoutesCommand::class);
    }

    public function testCouldBeConstructedWithConfigAndRouteCollectionAsArguments()
    {
        new RoutesCommand($this->createMock(ContainerInterface::class), 'default');
    }

    public function testShouldHaveCommandName()
    {
        $command = new RoutesCommand($this->createMock(ContainerInterface::class), 'default');

        $this->assertEquals('enqueue:routes', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new RoutesCommand($this->createMock(ContainerInterface::class), 'default');

        $this->assertEquals(['debug:enqueue:routes'], $command->getAliases());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new RoutesCommand($this->createMock(ContainerInterface::class), 'default');

        $options = $command->getDefinition()->getOptions();
        $this->assertCount(2, $options);

        $this->assertArrayHasKey('show-route-options', $options);
        $this->assertArrayHasKey('client', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new RoutesCommand($this->createMock(ContainerInterface::class), 'default');

        $arguments = $command->getDefinition()->getArguments();
        $this->assertCount(0, $arguments);
    }

    public function testShouldOutputEmptyRouteCollection()
    {
        $routeCollection = new RouteCollection([]);

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $this->createDriverStub(Config::create(), $routeCollection),
        ]), 'default');

        $tester = new CommandTester($command);

        $tester->execute([]);

        $expectedOutput = <<<'OUTPUT'
Found 0 routes


OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    public function testShouldUseFooDriver()
    {
        $routeCollection = new RouteCollection([
            new Route('fooTopic', Route::TOPIC, 'processor'),
        ]);

        $defaultDriverMock = $this->createMock(DriverInterface::class);
        $defaultDriverMock
            ->expects($this->never())
            ->method('getConfig')
        ;

        $defaultDriverMock
            ->expects($this->never())
            ->method('getRouteCollection')
        ;

        $fooDriverMock = $this->createDriverStub(Config::create(), $routeCollection);

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $defaultDriverMock,
            'enqueue.client.foo.driver' => $fooDriverMock,
        ]), 'default');

        $tester = new CommandTester($command);
        $tester->execute([
            '--client' => 'foo',
        ]);

        $this->assertContains('Found 1 routes', $tester->getDisplay());
    }

    public function testThrowIfClientNotFound()
    {
        $defaultDriverMock = $this->createMock(DriverInterface::class);
        $defaultDriverMock
            ->expects($this->never())
            ->method('getConfig')
        ;

        $defaultDriverMock
            ->expects($this->never())
            ->method('getRouteCollection')
        ;

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $defaultDriverMock,
        ]), 'default');

        $tester = new CommandTester($command);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Client "foo" is not supported.');
        $tester->execute([
            '--client' => 'foo',
        ]);
    }

    public function testShouldOutputTopicRouteInfo()
    {
        $routeCollection = new RouteCollection([
            new Route('fooTopic', Route::TOPIC, 'processor'),
            new Route('barTopic', Route::TOPIC, 'processor'),
        ]);

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $this->createDriverStub(Config::create(), $routeCollection),
        ]), 'default');

        $tester = new CommandTester($command);

        $tester->execute([]);

        $expectedOutput = <<<'OUTPUT'
Found 2 routes

+-------+----------+--------------------+-----------+----------+
| Type  | Source   | Queue              | Processor | Options  |
+-------+----------+--------------------+-----------+----------+
| topic | fooTopic | default (prefixed) | processor | (hidden) |
| topic | barTopic | default (prefixed) | processor | (hidden) |
+-------+----------+--------------------+-----------+----------+

OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    public function testShouldOutputCommandRouteInfo()
    {
        $routeCollection = new RouteCollection([
            new Route('fooCommand', Route::COMMAND, 'processor', ['foo' => 'fooVal', 'bar' => 'barVal']),
            new Route('barCommand', Route::COMMAND, 'processor', ['foo' => 'fooVal', 'bar' => 'barVal']),
        ]);

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $this->createDriverStub(Config::create(), $routeCollection),
        ]), 'default');

        $tester = new CommandTester($command);

        $tester->execute([]);

        $expectedOutput = <<<'OUTPUT'
Found 2 routes

+---------+------------+--------------------+-----------+----------+
| Type    | Source     | Queue              | Processor | Options  |
+---------+------------+--------------------+-----------+----------+
| command | fooCommand | default (prefixed) | processor | (hidden) |
| command | barCommand | default (prefixed) | processor | (hidden) |
+---------+------------+--------------------+-----------+----------+

OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    public function testShouldCorrectlyOutputPrefixedCustomQueue()
    {
        $routeCollection = new RouteCollection([
            new Route('fooCommand', Route::COMMAND, 'processor', ['queue' => 'foo']),
            new Route('barTopic', Route::TOPIC, 'processor', ['queue' => 'bar']),
        ]);

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $this->createDriverStub(Config::create(), $routeCollection),
        ]), 'default');

        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $this->assertSame(0, $exitCode);

        $expectedOutput = <<<'OUTPUT'
Found 2 routes

+---------+------------+----------------+-----------+----------+
| Type    | Source     | Queue          | Processor | Options  |
+---------+------------+----------------+-----------+----------+
| topic   | barTopic   | bar (prefixed) | processor | (hidden) |
| command | fooCommand | foo (prefixed) | processor | (hidden) |
+---------+------------+----------------+-----------+----------+

OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    public function testShouldCorrectlyOutputNotPrefixedCustomQueue()
    {
        $routeCollection = new RouteCollection([
            new Route('fooCommand', Route::COMMAND, 'processor', ['queue' => 'foo', 'prefix_queue' => false]),
            new Route('barTopic', Route::TOPIC, 'processor', ['queue' => 'bar', 'prefix_queue' => false]),
        ]);

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $this->createDriverStub(Config::create(), $routeCollection),
        ]), 'default');

        $tester = new CommandTester($command);

        $tester->execute([]);

        $expectedOutput = <<<'OUTPUT'
Found 2 routes

+---------+------------+-------------+-----------+----------+
| Type    | Source     | Queue       | Processor | Options  |
+---------+------------+-------------+-----------+----------+
| topic   | barTopic   | bar (as is) | processor | (hidden) |
| command | fooCommand | foo (as is) | processor | (hidden) |
+---------+------------+-------------+-----------+----------+

OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    public function testShouldCorrectlyOutputExternalRoute()
    {
        $routeCollection = new RouteCollection([
            new Route('fooCommand', Route::COMMAND, 'processor', ['external' => true]),
            new Route('barTopic', Route::TOPIC, 'processor', ['external' => true]),
        ]);

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $this->createDriverStub(Config::create(), $routeCollection),
        ]), 'default');

        $tester = new CommandTester($command);

        $tester->execute([]);

        $expectedOutput = <<<OUTPUT
Found 2 routes

+---------+------------+--------------------+----------------+----------+
| Type    | Source     | Queue              | Processor      | Options  |
+---------+------------+--------------------+----------------+----------+
| topic   | barTopic   | default (prefixed) | n\a (external) | (hidden) |
| command | fooCommand | default (prefixed) | n\a (external) | (hidden) |
+---------+------------+--------------------+----------------+----------+

OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    public function testShouldOutputRouteOptions()
    {
        $routeCollection = new RouteCollection([
            new Route('fooCommand', Route::COMMAND, 'processor', ['foo' => 'fooVal']),
            new Route('barTopic', Route::TOPIC, 'processor', ['bar' => 'barVal']),
        ]);

        $command = new RoutesCommand(new Container([
            'enqueue.client.default.driver' => $this->createDriverStub(Config::create(), $routeCollection),
        ]), 'default');

        $tester = new CommandTester($command);

        $tester->execute(['--show-route-options' => true]);

        $expectedOutput = <<<'OUTPUT'
Found 2 routes

+---------+------------+--------------------+-----------+----------------------+
| Type    | Source     | Queue              | Processor | Options              |
+---------+------------+--------------------+-----------+----------------------+
| topic   | barTopic   | default (prefixed) | processor | array (              |
|         |            |                    |           |   'bar' => 'barVal', |
|         |            |                    |           | )                    |
| command | fooCommand | default (prefixed) | processor | array (              |
|         |            |                    |           |   'foo' => 'fooVal', |
|         |            |                    |           | )                    |
+---------+------------+--------------------+-----------+----------------------+

OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function createDriverStub(Config $config, RouteCollection $routeCollection): DriverInterface
    {
        $driverMock = $this->createMock(DriverInterface::class);
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
