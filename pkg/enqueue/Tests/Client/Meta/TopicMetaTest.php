<?php

namespace Enqueue\Tests\Client\Meta;

use Enqueue\Client\Meta\TopicMeta;
use PHPUnit\Framework\TestCase;

class TopicMetaTest extends TestCase
{
    public function testCouldBeConstructedWithNameOnly()
    {
        $topic = new TopicMeta('aName');

        $this->assertAttributeEquals('aName', 'name', $topic);
        $this->assertAttributeEquals('', 'description', $topic);
        $this->assertAttributeEquals([], 'processors', $topic);
    }

    public function testCouldBeConstructedWithNameAndDescriptionOnly()
    {
        $topic = new TopicMeta('aName', 'aDescription');

        $this->assertAttributeEquals('aName', 'name', $topic);
        $this->assertAttributeEquals('aDescription', 'description', $topic);
        $this->assertAttributeEquals([], 'processors', $topic);
    }

    public function testCouldBeConstructedWithNameAndDescriptionAndSubscribers()
    {
        $topic = new TopicMeta('aName', 'aDescription', ['aSubscriber']);

        $this->assertAttributeEquals('aName', 'name', $topic);
        $this->assertAttributeEquals('aDescription', 'description', $topic);
        $this->assertAttributeEquals(['aSubscriber'], 'processors', $topic);
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $topic = new TopicMeta('theName', 'aDescription');

        $this->assertSame('theName', $topic->getName());
    }

    public function testShouldAllowGetDescriptionSetInConstructor()
    {
        $topic = new TopicMeta('aName', 'theDescription');

        $this->assertSame('theDescription', $topic->getDescription());
    }

    public function testShouldAllowGetSubscribersSetInConstructor()
    {
        $topic = new TopicMeta('aName', '', ['aSubscriber']);

        $this->assertSame(['aSubscriber'], $topic->getProcessors());
    }
}
