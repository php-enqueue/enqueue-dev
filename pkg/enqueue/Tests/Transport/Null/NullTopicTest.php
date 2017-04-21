<?php

namespace Enqueue\Tests\Transport\Null;

use Enqueue\Psr\PsrTopic;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullTopic;
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
