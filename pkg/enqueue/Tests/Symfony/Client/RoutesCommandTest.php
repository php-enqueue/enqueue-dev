<?php

namespace Enqueue\Tests\Symfony\Client;

use Enqueue\Client\Config;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Symfony\Client\RoutesCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RoutesCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, RoutesCommand::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(RoutesCommand::class);
    }

    public function testCouldBeConstructedWithConfigAndRouteCollectionAsArguments()
    {
        new RoutesCommand(Config::create(), new RouteCollection([]));
    }

    public function testShouldHaveCommandName()
    {
        $command = new RoutesCommand(Config::create(), new RouteCollection([]));

        $this->assertEquals('enqueue:routes', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new RoutesCommand(Config::create(), new RouteCollection([]));

        $this->assertEquals(['debug:enqueue:routes'], $command->getAliases());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new RoutesCommand(Config::create(), new RouteCollection([]));

        $options = $command->getDefinition()->getOptions();
        $this->assertCount(1, $options);

        $this->assertArrayHasKey('show-route-options', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new RoutesCommand(Config::create(), new RouteCollection([]));

        $arguments = $command->getDefinition()->getArguments();
        $this->assertCount(0, $arguments);
    }

    public function testShouldOutputEmptyRouteCollection()
    {
        $routeCollection = new RouteCollection([]);

        $command = new RoutesCommand(Config::create(), $routeCollection);

        $tester = new CommandTester($command);

        $tester->execute([]);

        $expectedOutput = <<<'OUTPUT'
Found 0 routes


OUTPUT;

        $this->assertCommandOutput($expectedOutput, $tester);
    }

    public function testShouldOutputTopicRouteInfo()
    {
        $routeCollection = new RouteCollection([
            new Route('fooTopic', Route::TOPIC, 'processor'),
            new Route('barTopic', Route::TOPIC, 'processor'),
        ]);

        $command = new RoutesCommand(Config::create(), $routeCollection);

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

        $command = new RoutesCommand(Config::create(), $routeCollection);

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

        $command = new RoutesCommand(Config::create(), $routeCollection);

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

        $command = new RoutesCommand(Config::create(), $routeCollection);

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

        $command = new RoutesCommand(Config::create(), $routeCollection);

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

        $command = new RoutesCommand(Config::create(), $routeCollection);

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

    private function assertCommandOutput(string $expected, CommandTester $tester): void
    {
        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($expected, $tester->getDisplay());
    }
}
