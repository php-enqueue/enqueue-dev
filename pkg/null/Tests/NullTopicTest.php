<?php

namespace Enqueue\Null\Tests;

use Enqueue\Null\NullTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrTopic;
use PHPUnit\Framework\TestCase;

class NullTopicTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(PsrTopic::class, NullTopic::class);
    }

    public function testCouldBeConstructedWithNameAsArgument()
    {
        new NullTopic('aName');
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $topic = new NullTopic('theName');

        $this->assertEquals('theName', $topic->getTopicName());
    }
}
