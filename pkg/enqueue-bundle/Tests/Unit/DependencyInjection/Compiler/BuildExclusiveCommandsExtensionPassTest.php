<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildExclusiveCommandsExtensionPass;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ExclusiveButQueueNameHardCodedCommandSubscriber;
use Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock\ExclusiveCommandSubscriber;
use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\ExclusiveCommandExtension;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildExclusiveCommandsExtensionPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPass()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildExclusiveCommandsExtensionPass::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildExclusiveCommandsExtensionPass();
    }

    public function testShouldDoNothingIfExclusiveCommandExtensionServiceNotRegistered()
    {
        $container = new ContainerBuilder();

        $pass = new BuildExclusiveCommandsExtensionPass();
        $pass->process($container);
    }

    public function testShouldReplaceFirstArgumentOfExclusiveCommandExtensionServiceConstructorWithExpectedMap()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.client.default_queue_name', 'default');
        $container->register('enqueue.client.exclusive_command_extension', ExclusiveCommandExtension::class)
            ->addArgument([])
        ;

        $processor = new Definition(ExclusiveCommandSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $pass = new BuildExclusiveCommandsExtensionPass();

        $pass->process($container);

        $this->assertEquals([
            'the-queue-name' => 'the-exclusive-command-name',
        ], $container->getDefinition('enqueue.client.exclusive_command_extension')->getArgument(0));
    }

    public function testShouldReplaceFirstArgumentOfExclusiveCommandConfiguredAsTagAttribute()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.client.default_queue_name', 'default');
        $container->register('enqueue.client.exclusive_command_extension', ExclusiveCommandExtension::class)
            ->addArgument([])
        ;

        $processor = new Definition($this->getMockClass(PsrProcessor::class));
        $processor->addTag('enqueue.client.processor', [
            'topicName' => Config::COMMAND_TOPIC,
            'processorName' => 'the-exclusive-command-name',
            'queueName' => 'the-queue-name',
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ]);
        $container->setDefinition('processor-id', $processor);

        $pass = new BuildExclusiveCommandsExtensionPass();

        $pass->process($container);

        $this->assertEquals([
            'the-queue-name' => 'the-exclusive-command-name',
        ], $container->getDefinition('enqueue.client.exclusive_command_extension')->getArgument(0));
    }

    public function testShouldThrowIfExclusiveSetTrueButQueueNameIsNotHardcoded()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.client.default_queue_name', 'default');
        $container->register('enqueue.client.exclusive_command_extension', ExclusiveCommandExtension::class)
            ->addArgument([])
        ;

        $processor = new Definition(ExclusiveButQueueNameHardCodedCommandSubscriber::class);
        $processor->addTag('enqueue.client.processor');
        $container->setDefinition('processor-id', $processor);

        $pass = new BuildExclusiveCommandsExtensionPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The exclusive command could be used only with queueNameHardcoded attribute set to true.');
        $pass->process($container);
    }
}
