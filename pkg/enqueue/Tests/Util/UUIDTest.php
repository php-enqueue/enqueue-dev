<?php

namespace Enqueue\Tests\Util;

use Enqueue\Util\UUID;
use PHPUnit\Framework\TestCase;

class UUIDTest extends TestCase
{
    public function testShouldGenerateUniqueId()
    {
        $uuid = UUID::generate();

        $this->assertInternalType('string', $uuid);
        $this->assertEquals(36, strlen($uuid));
    }

    public function testGeneratedValuesMustBeUnique()
    {
        $uuid1 = UUID::generate();
        $uuid2 = UUID::generate();
        $uuid3 = UUID::generate();

        $this->assertNotEquals($uuid1, $uuid2);
        $this->assertNotEquals($uuid2, $uuid3);
        $this->assertNotEquals($uuid1, $uuid3);
    }
}
