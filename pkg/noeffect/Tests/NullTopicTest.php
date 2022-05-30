<?php

namespace Enqueue\NoEffect\Tests;

use Enqueue\NoEffect\NullTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;

class NullTopicTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(Topic::class, NullTopic::class);
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
