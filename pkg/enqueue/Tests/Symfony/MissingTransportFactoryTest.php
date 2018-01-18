<?php

namespace Enqueue\Tests\Symfony;

use Enqueue\Symfony\MissingTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class MissingTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, MissingTransportFactory::class);
    }

    public function testCouldBeConstructedWithNameAndPackages()
    {
        $transport = new MissingTransportFactory('aMissingTransportName', ['aPackage', 'anotherPackage']);

        $this->assertEquals('aMissingTransportName', $transport->getName());
    }

    public function testThrowOnProcessForOnePackageToInstall()
    {
        $transport = new MissingTransportFactory('aMissingTransportName', ['aFooPackage']);
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "foo": In order to use the transport "aMissingTransportName" install a package "aFooPackage"');
        $processor->process($tb->buildTree(), [[]]);
    }

    public function testThrowOnProcessForSeveralPackagesToInstall()
    {
        $transport = new MissingTransportFactory('aMissingTransportName', ['aFooPackage', 'aBarPackage']);
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "foo": In order to use the transport "aMissingTransportName" install one of the packages "aFooPackage", "aBarPackage"');
        $processor->process($tb->buildTree(), [[]]);
    }

    public function testThrowEvenIfThereAreSomeOptionsPassed()
    {
        $transport = new MissingTransportFactory('aMissingTransportName', ['aFooPackage', 'aBarPackage']);
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('In order to use the transport "aMissingTransportName"');
        $processor->process($tb->buildTree(), [[
            'foo' => 'fooVal',
            'bar' => 'barVal',
        ]]);
    }
}
