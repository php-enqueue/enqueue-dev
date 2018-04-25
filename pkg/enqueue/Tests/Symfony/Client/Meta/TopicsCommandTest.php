<?php

namespace Enqueue\Tests\Symfony\Client\Meta;

use Enqueue\Client\Meta\TopicMetaRegistry;
use Enqueue\Symfony\Client\Meta\TopicsCommand;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TopicsCommandTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, TopicsCommand::class);
    }

    public function testCouldBeConstructedWithTopicMetaRegistryAsFirstArgument()
    {
        new TopicsCommand(new TopicMetaRegistry([]));
    }

    public function testShouldHaveCommandName()
    {
        $command = new TopicsCommand(new TopicMetaRegistry([]));

        $this->assertEquals('enqueue:topics', $command->getName());
    }

    public function testShouldHaveCommandAliases()
    {
        $command = new TopicsCommand(new TopicMetaRegistry([]));

        $this->assertEquals(['enq:m:t', 'debug:enqueue:topics'], $command->getAliases());
    }

    public function testShouldShowMessageFoundZeroTopicsIfAnythingInRegistry()
    {
        $command = new TopicsCommand(new TopicMetaRegistry([]));

        $output = $this->executeCommand($command);

        $this->assertContains('Found 0 topics', $output);
    }

    public function testShouldShowMessageFoundTwoTopics()
    {
        $command = new TopicsCommand(new TopicMetaRegistry([
            'fooTopic' => [],
            'barTopic' => [],
        ]));

        $output = $this->executeCommand($command);

        $this->assertContains('Found 2 topics', $output);
    }

    public function testShouldShowInfoAboutTopics()
    {
        $command = new TopicsCommand(new TopicMetaRegistry([
            'fooTopic' => ['description' => 'fooDescription', 'processors' => ['fooSubscriber']],
            'barTopic' => ['description' => 'barDescription', 'processors' => ['barSubscriber']],
        ]));

        $output = $this->executeCommand($command);

        $this->assertContains('fooTopic', $output);
        $this->assertContains('fooDescription', $output);
        $this->assertContains('fooSubscriber', $output);
        $this->assertContains('barTopic', $output);
        $this->assertContains('barDescription', $output);
        $this->assertContains('barSubscriber', $output);
    }

    /**
     * @param Command  $command
     * @param string[] $arguments
     *
     * @return string
     */
    protected function executeCommand(Command $command, array $arguments = [])
    {
        $tester = new CommandTester($command);
        $tester->execute($arguments);

        return $tester->getDisplay();
    }
}
