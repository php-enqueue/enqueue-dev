<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanMessage;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class GearmanMessageTest extends TestCase
{
    use ClassExtensionTrait;
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    public function testShouldAllowGetJobPreviouslySet()
    {
        $job = new \GearmanJob();

        $message = new GearmanMessage();
        $message->setJob($job);

        $this->assertSame($job, $message->getJob());
    }
}
